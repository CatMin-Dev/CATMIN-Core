<?php

declare(strict_types=1);

$catminOfficialDevPublicKey = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAuLwgkC1exql3N08w++4s
DN6+YLC8++fgNYz+m2/4lgo+iJVku9mAyUJpYQG1IsYXNRwHMllRefh+nIs89b+N
4qX/HT6CZK13MVKPJ5s/oqw5ybuHuvF5L5nMzhWMxHXggFXtlMZXTSGviZxlnumq
GWs5jRXMwyANUhq+zMxg8StLOV5YRtXwblmGwtv/W2A0kwqjmh3a1hm4KCllEzQD
eHhfGpIlRL2XM6B3QhCrk4G8zmZyKoE5jY2D119V+Mp+V4DcwbMTHAKhWKd96EzX
UYKFuyQ3k0daOYE5vhxncqjFFtocLgWZgMkGqLltvdWqFW+plVcQ0EdXhnshsK5e
ucTJzsyyMxgerfZem+Ulc8ai0QGdBCQ07TQXZtUmJe6OnnvMV0MpULzxsOCIISUa
T8R94kigOxBv0pxJxYjQKYFVSJ/DShDJJWCfFOIoG9uSe9FBkJCrLlF0EObOm+sS
oPAqgpWrMl6MR1Cr4TdtGsmgsn+OP9FJVPI5svFwAJyB2Jr7IS4guK4/AJKR/qmy
ZhtuOUSBtuHoU/76yhSDZd2fxcnuoFMx9GyBOExf58XjoACXsOWa5R459DlIepaA
4NYkJ/Y+ytNl8ulvGhgOMySXoUvWAj+7n5fFCpaBe/FrAwsIWiOL7gsSZb4Bulp6
wurFARwMCQ5dC6bO2EkP2g0CAwEAAQ==
-----END PUBLIC KEY-----
PEM;

return [
    // permissive|recommended|strict
    'mode' => 'strict',
    'official_publishers' => [
        'catmin dev',
        'catmin-dev',
        'catmin',
    ],
    'keyring' => [
        'catmin-official-dev-20260410' => $catminOfficialDevPublicKey,
    ],
];
