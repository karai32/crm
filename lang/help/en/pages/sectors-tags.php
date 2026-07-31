<?php

return array (
  'id' => 'sectors-tags',
  'title' => 'Sectors and tags',
  'description' => 'Classifying records with industries and flexible labels.',
  'icon' => 'ph-tag',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'classification-principles',
      'title' => 'Classification principles',
      'paragraphs' =>
      array (
        0 => 'Sectors and tags help organize the database into a consistent structure and then quickly find the required clients and contacts with filters. These tools complement each other but serve different purposes: a sector describes a client’s main line of business, while tags identify additional characteristics that may change over time.',
        1 => 'Choose a sector when you need to answer the question “which industry does this client belong to?”. A client can have only one sector. A tag is suitable for more flexible classification: it can indicate priority, source, participation in a campaign, interest in a service, or any other working characteristic. A single record can have several tags.',
        2 => 'To keep classification useful, agree on consistent names in advance. For example, do not create “Important,” “Priority,” and “VIP” tags at the same time if they mean the same thing. You should also avoid repeating a client’s industry as a tag when it is already specified by the sector. The more consistently sectors and tags are used, the more accurate the filters are and the easier it is to analyze the database.',
      ),
    ),
    1 =>
    array (
      'id' => 'sectors',
      'title' => 'Sectors',
      'paragraphs' =>
      array (
        0 => 'A sector is a client’s main industry or line of business, such as “Tourism,” “Real estate,” or “Technology.” Sectors are assigned only to clients; a contact does not have a sector of its own. The contact list can, however, be filtered by the sector of a related client.',
        1 => 'A client can have only one selected sector. The sector list should therefore remain broad and stable rather than becoming a list of individual services, projects, or short-term states. Tags or custom fields are better suited to those details.',
        2 => 'Only a name is required when creating a sector. You can later choose an icon and change its active status. The icon is used only as a visual marker for the sector. Changing the name or icon affects every client that already has that sector.',
        3 => 'If a sector is already used by clients, the system does not delete it permanently but makes it inactive. Its relationships with existing clients remain intact, but an inactive sector cannot be assigned to new clients. A sector that is not used anywhere can be deleted completely.',
      ),
    ),
    2 =>
    array (
      'id' => 'tags',
      'title' => 'Tags',
      'paragraphs' =>
      array (
        0 => 'Tags are flexible labels that can be assigned to both clients and contacts. One record can have several tags at the same time. For example, clients can be tagged by relationship type or priority, while contacts can be tagged by lead source, service of interest, marketing campaign, or processing stage.',
        1 => 'Each tag has a name and an optional color. The color makes labels easier to distinguish in lists and records but does not otherwise change the system’s behavior. Tags can be added and removed in an individual record or in bulk for several selected clients or contacts. Both sections provide tag filters.',
        2 => 'Tags can also be assigned automatically during import and when data is submitted through the API. Integrations should send the same agreed names consistently: spelling differences can create unnecessary labels and make searching more difficult.',
        3 => 'Tags do not have an active status. Renaming a tag or changing its color is immediately reflected in all related records. Deleting a tag permanently removes it from all clients and contacts, but does not delete the clients or contacts themselves. If a label is no longer needed only for some records, remove it from those records instead of deleting it from the entire system.',
      ),
    ),
  ),
);
