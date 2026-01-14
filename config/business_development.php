<?php

return [
    'b2b_logic' => [
        'corporate_accounts' => [
            'enabled' => true,
            'min_employees' => 10,
            'pricing_tier' => 'enterprise',
            'custom_contracts' => true,
        ],
        'partnerships' => [
            'affiliate_program' => true,
            'reseller_program' => false,
            'white_label' => false,
        ],
        'sales_pipeline' => [
            'stages' => ['prospect', 'qualified', 'proposal', 'negotiation', 'closed'],
            'automated_follow_ups' => true,
            'crm_integration' => true,
        ],
    ],
    'contract_rules' => [
        'templates' => [
            'standard' => 'standard_contract.pdf',
            'enterprise' => 'enterprise_contract.pdf',
            'custom' => 'custom_contract.pdf',
        ],
        'auto_renewal' => [
            'enabled' => true,
            'notice_period' => 30, // days
            'renewal_terms' => 'same_as_original',
        ],
        'termination_clauses' => [
            'breach' => true,
            'non_payment' => true,
            'mutual_agreement' => true,
        ],
        'compliance' => [
            'gdpr' => true,
            'data_processing' => true,
            'audit_logs' => true,
        ],
    ],
    'expansion_strategies' => [
        'market_penetration' => [
            'target_regions' => ['Sulawesi', 'Java', 'Bali'],
            'pricing_adjustments' => true,
        ],
        'product_diversification' => [
            'new_services' => ['consulting', 'training', 'certification'],
            'bundled_offers' => true,
        ],
        'strategic_alliances' => [
            'potential_partners' => ['universities', 'corporations', 'government'],
            'collaboration_models' => ['joint_ventures', 'co-marketing'],
        ],
    ],
];
