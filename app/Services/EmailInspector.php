<?php

class EmailInspector
{
    // ~220 most common free/consumer email domains.
    // If a contact's domain is NOT in this list → marked as corporate.
    private static array $freeDomains = [
        // Google
        'gmail.com', 'googlemail.com',
        // Microsoft
        'hotmail.com', 'hotmail.co.uk', 'hotmail.fr', 'hotmail.de', 'hotmail.es', 'hotmail.it',
        'hotmail.com.br', 'hotmail.com.ar', 'hotmail.com.mx', 'hotmail.be', 'hotmail.nl',
        'hotmail.se', 'hotmail.no', 'hotmail.dk', 'hotmail.fi', 'hotmail.com.tr',
        'outlook.com', 'outlook.co.uk', 'outlook.fr', 'outlook.de', 'outlook.es', 'outlook.it',
        'outlook.com.br', 'outlook.com.ar', 'outlook.com.mx', 'outlook.be', 'outlook.nl',
        'live.com', 'live.co.uk', 'live.fr', 'live.de', 'live.es', 'live.it',
        'live.com.br', 'live.com.ar', 'live.com.mx', 'live.be', 'live.nl',
        'msn.com', 'passport.com', 'windowslive.com',
        // Yahoo
        'yahoo.com', 'yahoo.co.uk', 'yahoo.fr', 'yahoo.de', 'yahoo.es', 'yahoo.it',
        'yahoo.com.br', 'yahoo.com.ar', 'yahoo.com.mx', 'yahoo.co.jp', 'yahoo.co.in',
        'yahoo.ca', 'yahoo.com.au', 'yahoo.co.nz', 'yahoo.co.id', 'yahoo.com.ph',
        'yahoo.com.sg', 'yahoo.com.hk', 'yahoo.ro', 'yahoo.gr', 'yahoo.pl',
        'ymail.com', 'rocketmail.com',
        // Apple
        'icloud.com', 'me.com', 'mac.com',
        // Proton
        'protonmail.com', 'protonmail.ch', 'proton.me', 'pm.me',
        // Tutanota
        'tutanota.com', 'tutanota.de', 'tutamail.com', 'tuta.io', 'keemail.me',
        // AOL
        'aol.com', 'aol.co.uk', 'aol.fr', 'aol.de', 'aol.at', 'aim.com',
        // GMX
        'gmx.com', 'gmx.net', 'gmx.de', 'gmx.at', 'gmx.ch', 'gmx.us',
        // German
        'web.de', 'freenet.de', 'arcor.de', 't-online.de', 'online.de',
        // Mail.com group
        'mail.com', 'email.com', 'usa.com', 'europe.com', 'asia.com', 'writeme.com',
        'cheerful.com', 'myself.com', 'hailmail.net', 'inbox.com', 'netscape.net',
        // Russian
        'mail.ru', 'yandex.ru', 'yandex.com', 'yandex.ua', 'yandex.by', 'yandex.kz',
        'rambler.ru', 'inbox.ru', 'bk.ru', 'list.ru', 'internet.ru', 'ya.ru',
        // French
        'laposte.net', 'free.fr', 'sfr.fr', 'orange.fr', 'wanadoo.fr', 'numericable.fr',
        'bouyguestelecom.fr', 'bbox.fr',
        // Italian
        'libero.it', 'virgilio.it', 'alice.it', 'tin.it', 'tiscali.it', 'supereva.it',
        'inwind.it', 'iol.it', 'email.it',
        // Polish
        'wp.pl', 'onet.pl', 'o2.pl', 'interia.pl', 'poczta.fm', 'gazeta.pl',
        // Czech
        'seznam.cz', 'centrum.cz', 'volny.cz', 'email.cz', 'post.cz',
        // Brazilian
        'bol.com.br', 'uol.com.br', 'terra.com.br', 'globo.com', 'ig.com.br', 'r7.com',
        // Fastmail (personal)
        'fastmail.com', 'fastmail.fm', 'fastmail.net', 'fastmail.org',
        // Zoho personal
        'zoho.com', 'zohomail.com',
        // Ukrainian
        'ukr.net', 'i.ua', 'meta.ua',
        // Finnish
        'netti.fi', 'luukku.com', 'elisanet.fi',
        // Dutch
        'planet.nl', 'xs4all.nl', 'chello.nl', 'hetnet.nl',
        // Belgian
        'telenet.be', 'skynet.be',
        // UK
        'blueyonder.co.uk', 'ntlworld.com', 'btinternet.com', 'sky.com', 'talktalk.net',
        'virgin.net', 'virginmedia.com', 'o2.co.uk', 'lineone.net',
        // Swiss
        'swisscom.ch', 'bluewin.ch', 'hispeed.ch',
        // Australian
        'bigpond.com', 'bigpond.net.au', 'optusnet.com.au', 'internode.on.net',
        // Canadian
        'rogers.com', 'sympatico.ca', 'telus.net', 'shaw.ca', 'bell.net',
        // New Zealand
        'xtra.co.nz', 'slingshot.co.nz',
        // Irish
        'eircom.net', 'ireland.com',
        // Portuguese
        'sapo.pt', 'netcabo.pt', 'telepac.pt', 'iol.pt',
        // Spanish
        'terra.es', 'telefonica.net', 'ono.com',
        // Hungarian
        'freemail.hu', 'citromail.hu', 'indamail.hu',
        // Chinese
        '163.com', '126.com', 'qq.com', 'sina.com', 'sina.cn', 'sohu.com',
        '21cn.com', 'tom.com', 'yeah.net', 'aliyun.com',
        // Korean
        'naver.com', 'daum.net', 'hanmail.net', 'nate.com',
        // Indian
        'rediffmail.com', 'indiatimes.com', 'sify.com',
        // Bulgarian
        'abv.bg', 'mail.bg', 'gbg.bg',
        // Misc
        'lycos.com', 'excite.com', 'hushmail.com',
        // Disposable / temp
        'yopmail.com', 'mailinator.com', 'guerrillamail.com', 'guerrillamail.info',
        'guerrillamail.net', 'guerrillamail.org', 'guerrillamail.de', 'sharklasers.com',
        'spam4.me', 'trashmail.com', 'trashmail.me', 'trashmail.net', 'mytrashmail.com',
        'maildrop.cc', '10minutemail.com', 'mailnull.com', 'spamgourmet.com',
        'throwam.com', 'dispostable.com', 'mailforspam.com',
    ];

    /**
     * Inspect an email address and return is_corporate_email + email_status.
     * Returns nulls when email is absent.
     *
     * $checkDns performs a live MX lookup and must stay disabled for bulk
     * loops (e.g. CSV import) -- thousands of blocking DNS calls in one
     * request will exceed the PHP/nginx timeout.
     */
    public static function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function inspect(?string $email, bool $checkDns = true): array
    {
        if ($email === null || trim($email) === '') {
            return ['is_corporate_email' => null, 'email_status' => null];
        }

        if (!self::isValid($email)) {
            return ['is_corporate_email' => null, 'email_status' => 'invalid'];
        }

        $domain = strtolower(substr($email, strrpos($email, '@') + 1));

        $isCorporate = !in_array($domain, self::$freeDomains, true) ? 1 : 0;

        if (!$checkDns) {
            return ['is_corporate_email' => $isCorporate, 'email_status' => 'unknown'];
        }

        $hasMx = @checkdnsrr($domain, 'MX');
        $status = $hasMx ? 'valid' : 'invalid';

        return ['is_corporate_email' => $isCorporate, 'email_status' => $status];
    }
}
