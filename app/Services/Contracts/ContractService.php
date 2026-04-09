<?php

namespace App\Services\Contracts;

use App\Enums\ContractStatus;
use App\Enums\PaymentCycle;
use App\Enums\VendorActiveStatus;
use App\Enums\VendorKind;
use App\Models\Contract;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Contract\PaymentScheduleBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(
        protected PaymentService $payments,
        protected ContractAuditService $audit
    ) {}

    private function resolveVendorByName(string $name): Vendor
    {
        $existing = Vendor::withTrashed()->where('name', $name)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        return Vendor::query()->create([
            'name' => $name,
            'kind' => VendorKind::Active,
            'status' => VendorActiveStatus::Active->value,
        ]);
    }

    public function create(array $data, User $creator): Contract
    {
        return DB::transaction(function () use ($data, $creator) {
            $vendor = $this->resolveVendorByName(trim($data['vendor_name']));
            $product = Product::query()->firstOrCreate(
                ['name' => trim($data['product_name'])],
                []
            );

            $contract = Contract::query()->create([
                'code' => $this->generateUniqueCode(),
                'vendor_id' => $vendor->id,
                'product_id' => $product->id,
                'department_id' => $data['department_id'],
                'scope' => $data['scope'] ?? null,
                'status' => ContractStatus::Draft,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_value' => $data['total_value'],
                'payment_cycle' => PaymentCycle::from($data['payment_cycle']),
                'created_by' => $creator->id,
                'approved_by' => null,
                'followed_by_id' => $data['followed_by_id'] ?? null,
            ]);

            $this->rebuildInstallmentSchedule($contract);

            $this->audit->log(
                $contract,
                'contract.created',
                null,
                $contract->fresh()->toArray(),
                $creator->id
            );

            return $contract->fresh(['vendor', 'product', 'department', 'payments', 'followedBy']);
        });
    }

    public function update(Contract $contract, array $data, User $user): Contract
    {
        if ($contract->status !== ContractStatus::Draft) {
            abort(422, 'Only draft contracts can be edited.');
        }

        return DB::transaction(function () use ($contract, $data, $user) {
            $snapshotBefore = $contract->toArray();

            $vendorId = $contract->vendor_id;
            $productId = $contract->product_id;
            if (array_key_exists('vendor_name', $data) && $data['vendor_name'] !== null && trim((string) $data['vendor_name']) !== '') {
                $vendorId = $this->resolveVendorByName(trim($data['vendor_name']))->id;
            }
            if (array_key_exists('product_name', $data) && $data['product_name'] !== null && trim((string) $data['product_name']) !== '') {
                $productId = Product::query()->firstOrCreate(
                    ['name' => trim($data['product_name'])],
                    []
                )->id;
            }

            $contract->fill([
                'vendor_id' => $vendorId,
                'product_id' => $productId,
                'department_id' => $data['department_id'] ?? $contract->department_id,
                'scope' => array_key_exists('scope', $data) ? $data['scope'] : $contract->scope,
                'start_date' => $data['start_date'] ?? $contract->start_date,
                'end_date' => $data['end_date'] ?? $contract->end_date,
                'total_value' => $data['total_value'] ?? $contract->total_value,
                'payment_cycle' => isset($data['payment_cycle'])
                    ? PaymentCycle::from($data['payment_cycle'])
                    : $contract->payment_cycle,
            ]);
            $contract->save();

            $this->rebuildInstallmentSchedule($contract->fresh());

            $this->audit->log(
                $contract,
                'contract.updated',
                $snapshotBefore,
                $contract->fresh()->toArray(),
                $user->id
            );

            return $contract->fresh(['vendor', 'product', 'department', 'payments', 'followedBy']);
        });
    }

    public function delete(Contract $contract, User $user): void
    {
        if ($user->role !== 'admin' && $contract->status !== ContractStatus::Draft) {
            abort(422, 'Only draft contracts can be deleted.');
        }

        DB::transaction(function () use ($contract, $user) {
            $snapshot = $contract->toArray();
            $this->audit->log($contract, 'contract.deleted', $snapshot, null, $user->id);
            $contract->delete();
        });
    }

    public function terminate(Contract $contract, User $user): Contract
    {
        if ($contract->status !== ContractStatus::Active) {
            abort(422, 'Only active contracts can be terminated.');
        }

        $previous = $contract->only(['status']);
        $contract->update(['status' => ContractStatus::Terminated]);

        $this->audit->log(
            $contract,
            'contract.terminated',
            $previous,
            $contract->only(['status']),
            $user->id
        );

        return $contract->fresh();
    }

    /**
     * Mark active contracts past end_date as expired.
     */
    public function syncExpiredStatuses(): int
    {
        return Contract::query()
            ->where('status', ContractStatus::Active->value)
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['status' => ContractStatus::Expired->value]);
    }

    private function rebuildInstallmentSchedule(Contract $contract): void
    {
        $rows = PaymentScheduleBuilder::build(
            $contract->start_date,
            $contract->end_date,
            (string) $contract->total_value,
            $contract->payment_cycle
        );

        $this->payments->replaceSchedule($contract, $rows);
    }

    public function restoreFromTrash(Contract $contract, User $user): Contract
    {
        if (! $contract->trashed()) {
            abort(422, 'Contract is not in trash.');
        }

        return DB::transaction(function () use ($contract, $user) {
            $contract->restore();

            $this->audit->log(
                $contract,
                'contract.restored',
                null,
                $contract->fresh()->toArray(),
                $user->id
            );

            return $contract->fresh(['vendor', 'product', 'department', 'payments', 'followedBy']);
        });
    }

    public function forceDeletePermanent(Contract $contract, User $user): void
    {
        if (! $contract->trashed()) {
            abort(422, 'Contract must be in trash before permanent deletion.');
        }

        DB::transaction(function () use ($contract, $user) {
            $snapshot = $contract->toArray();
            $this->audit->log($contract, 'contract.force_deleted', $snapshot, null, $user->id);
            $contract->forceDelete();
        });
    }

    private function generateUniqueCode(): string
    {
        for ($i = 0; $i < 15; $i++) {
            $code = 'CNT-'.now()->format('Y').'-'.strtoupper(Str::random(6));
            if (! Contract::query()->withTrashed()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate a unique contract code.');
    }
}
