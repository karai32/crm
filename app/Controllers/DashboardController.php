<?php

class DashboardController
{
    private DashboardRepository $dashboardRepository;

    public function __construct()
    {
        $this->dashboardRepository = new DashboardRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        View::render('dashboard/index', [
            'title'  => 'Dashboard',
            'styles' => ['dashboard.css'],
            'user'   => Auth::user(),
            'stats'  => [
                'contacts' => $this->dashboardRepository->countContacts(),
                'clients' => $this->dashboardRepository->countClients(),
                'sectors' => $this->dashboardRepository->countSectors(),
                'tags' => $this->dashboardRepository->countTags(),
            ],
            'latestContacts' => $this->dashboardRepository->latestContacts(5),
            'topClients' => $this->dashboardRepository->clientsWithMostContacts(10),
            'clientsBySector' => $this->dashboardRepository->clientsBySector(),
        ]);
    }
}
