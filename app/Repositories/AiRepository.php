<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

class AiRepository
{
    // int
    public function countMissingCompany(): int
    {
        return $this->missingCompany(Database::table('contacts'))->count();
    }

    // int
    public function countMissingCompanyDomains(): int
    {
        return $this->missingCompany(Database::table('contacts'))
            ->distinct()
            ->count(Database::raw("SUBSTRING_INDEX(contacts.email, '@', -1)"));
    }

    // array<{id, full_name, email, domain, domain_contacts}>
    public function paginateMissingCompany(int $page, int $perPage, string $sort = 'domain', string $dir = 'asc'): array
    {
        $allowed = [
            'id'              => 'contacts.id',
            'full_name'       => 'contacts.full_name',
            'email'           => 'contacts.email',
            'domain'          => 'domain',
            'domain_contacts' => 'domain_contacts',
        ];
        $domains = $this->missingCompany(Database::table('contacts'))
            ->selectRaw("SUBSTRING_INDEX(contacts.email, '@', -1) AS domain")
            ->selectRaw('COUNT(*) AS contacts_count')
            ->groupByRaw("SUBSTRING_INDEX(contacts.email, '@', -1)");

        $query = Database::table('contacts')
            ->joinSub($domains, 'domains', fn (JoinClause $join) => $join->on(
                'domains.domain',
                '=',
                Database::raw("SUBSTRING_INDEX(contacts.email, '@', -1)")
            ))
            ->select(['contacts.id', 'contacts.full_name', 'contacts.email'])
            ->selectRaw("SUBSTRING_INDEX(contacts.email, '@', -1) AS domain")
            ->addSelect('domains.contacts_count AS domain_contacts');

        return Database::rows(
            $this->missingCompany($query)
                ->orderBy($allowed[$sort] ?? 'domain', $dir === 'desc' ? 'desc' : 'asc')
                ->orderBy('contacts.id')
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // Contacts eligible for AI company lookup: corporate email, no company name, not yet reviewed.
    private function missingCompany(Builder $query): Builder
    {
        return $query
            ->where('contacts.is_corporate_email', 1)
            ->whereRaw("TRIM(contacts.company) = ''")
            ->whereNotNull('contacts.email')
            ->where('contacts.email', 'like', '%@%')
            ->whereNull('contacts.company_change_date');
    }
}
