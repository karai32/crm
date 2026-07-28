# Help content

The help center content is stored outside the controller and grouped by locale.

```text
lang/help/
├── ru/
│   ├── index.php
│   ├── pages/
│   └── technical/
├── en/
└── es/
```

Each locale has the same public page identifiers and order. `index.php` contains
the interface copy, the technical-section metadata, and requires the page files
in menu order.

Regular help articles live in `pages/<id>.php`. Developer documentation lives
in `technical/<id>.php`. A page file returns this structure:

```php
return [
    'title' => 'Page title',
    'description' => 'Short page description.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'unique-section-id',
            'title' => 'Section title',
            'paragraphs' => ['Paragraph text.'],
            'examples' => [
                ['title' => 'Example title', 'code' => 'Example body'],
            ],
        ],
    ],
];
```

Large pages may assemble `sections` from smaller files. The Russian database
article is the current example of this layout.

When adding a page:

1. Create the page file in every locale, keeping the same ID.
2. Add its `require` entry to every locale's `index.php` in the intended order.
3. Keep user-facing prose out of `HelpController`.
4. Keep section IDs unique within the page and stable after publication.
5. Run PHP lint for all files under `lang/help` and render the page once to
   verify section and example output.

English and Spanish files may temporarily contain placeholder text, but the
locale structures must remain aligned so switching language never removes a
menu item or produces a missing page.
