<?php

class AiController
{
    use SortableTrait;

    private AiRepository $ai;

    public function __construct()
    {
        $this->ai = new AiRepository();
    }

    public function index(): void
    {
        $sort = $this->sortParam(['id', 'full_name', 'email', 'domain', 'domain_contacts'], 'domain');
        $dir  = $this->dirParam('asc');

        $total = $this->ai->countMissingCompany();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('ai/index', [
            'title'        => Lang::get('ai.title'),
            'styles'       => ['settings.css', 'ai.css'],
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
}
