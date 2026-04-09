<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\MarkPaymentPaidRequest;
use App\Http\Resources\ContractPaymentResource;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Services\Contracts\PaymentService;
use Illuminate\Http\Request;

class ContractPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $payments
    ) {}

    public function upcoming(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        $days = (int) $request->query('days', 30);

        return ContractPaymentResource::collection(
            $this->payments->upcomingInstallmentsWithinDays($days)
        );
    }

    public function index(Contract $contract)
    {
        $this->authorize('view', $contract);

        return ContractPaymentResource::collection(
            $this->payments->orderedInstallments($contract)
        );
    }

    public function markPaid(MarkPaymentPaidRequest $request, Contract $contract, ContractPayment $payment)
    {
        $this->authorize('markPayment', $contract);

        return new ContractPaymentResource(
            $this->payments->markPaidForContract(
                $contract,
                $payment,
                $request->user(),
                (string) $request->validated('paid_amount'),
                $request->file('file')
            )
        );
    }
}
