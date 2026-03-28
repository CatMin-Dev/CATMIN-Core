<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reinitialisation mot de passe admin</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111; line-height: 1.5;">
    <h2>CATMIN - Reinitialisation mot de passe admin</h2>
    <p>Une demande de reinitialisation de mot de passe a ete recue pour votre acces administration.</p>
    <p>Ce lien est valide pendant {{ $expiresInMinutes }} minute(s) et utilisable une seule fois.</p>
    <p>
        <a href="{{ $resetUrl }}" style="display:inline-block;padding:10px 14px;background:#0b5ed7;color:#fff;text-decoration:none;border-radius:4px;">
            Reinitialiser mon mot de passe
        </a>
    </p>
    <p>Si vous n'etes pas a l'origine de cette demande, ignorez ce message.</p>
</body>
</html>
