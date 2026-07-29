<?php

class DashboardController
{
    private DashboardRepository $dashboardRepository;
    private ContactRepository $contacts;

    public function __construct()
    {
        $this->dashboardRepository = new DashboardRepository();
        $this->contacts = new ContactRepository();
    }

    public function index(): void
    {
        $latestContacts = $this->dashboardRepository->latestContacts(25);
        $contactIds = array_map('intval', array_column($latestContacts, 'id'));

        View::render('dashboard/index', [
            'title'  => Lang::get('dashboard.title'),
            'styles' => ['dashboard.css'],
            'user'   => Auth::user(),
            'stats'  => [
                'contacts' => $this->dashboardRepository->countContacts(),
                'clients' => $this->dashboardRepository->countClients(),
                'sectors' => $this->dashboardRepository->countSectors(),
                'tags' => $this->dashboardRepository->countTags(),
            ],
            'latestContacts'        => $latestContacts,
            'latestContactClients'  => $this->contacts->clientsForContacts($contactIds),
            'topClients' => $this->dashboardRepository->clientsWithMostContacts(10),
            'clientsBySector' => $this->dashboardRepository->clientsBySector(),
        ]);
    }
}
