<?php

namespace App\Enums;

/**
 * Lifecycle stages for contracted (active) vendors.
 */
enum VendorTimelinePhase: string
{
    case PotentialContact = 'potential_contact';
    case SurveyConsult = 'survey_consult';
    case Quotation = 'quotation';
    case Negotiation = 'negotiation';
    case ContractSigned = 'contract_signed';
    case PaymentAcceptance = 'payment_acceptance';
    case NoContract = 'no_contract';
    /** Optional lightweight events for research vendors */
    case ResearchUpdate = 'research_update';
}
