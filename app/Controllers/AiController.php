<?php

class AiController
{
    use SortableTrait;

    private AiRepository $ai;
    private SectorRepository $sectors;

    public function __construct()
    {
        $this->ai = new AiRepository();
        $this->sectors = new SectorRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit;
        }

        $sort = $this->sortParam(['id', 'full_name', 'email', 'domain', 'domain_contacts'], 'domain');
        $dir  = $this->dirParam('asc');

        $total = $this->ai->countMissingCompany();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('ai/index', [
            'title'        => Lang::get('ai.title'),
            'styles'       => ['settings.css', 'ai.css', 'data.css'],
            'scripts'      => ['ai.js'],
            'contacts'     => $this->ai->paginateMissingCompany($page, $perPage, $sort, $dir),
            'domainsTotal' => $this->ai->countMissingCompanyDomains(),
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'totalPages'   => $totalPages,
            'sort'         => $sort,
            'dir'          => $dir,
        ]);
    }

    public function clients(): void
    {
        Auth::requireLogin();

        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit;
        }

        $sort = $this->sortParam(['id', 'commercial_name', 'legal_name', 'website'], 'commercial_name');
        $dir  = $this->dirParam('asc');

        $total = $this->ai->countMissingClientEnrichment();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('ai/clients', [
            'title'      => Lang::get('ai.clients_title'),
            'styles'     => ['settings.css', 'ai.css', 'data.css'],
            'scripts'    => ['ai-clients.js'],
            'clients'    => $this->ai->paginateMissingClientEnrichment($page, $perPage, $sort, $dir),
            'sectors'    => $this->sectors->active(),
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }
}
