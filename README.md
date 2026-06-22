# BPM Agency — Devis Spectacle Bollywood

Page de devis interactive pour la **soirée d'entreprise BPM Agency**.

- **Client :** BPM Agency — Fanny Servant (fanny.servant@bpmagency.fr)
- **Lieu :** Pullman Bordeaux, Avenue Jean-Gabriel Domergue, 33300 Bordeaux
- **Date :** 12/12/2026
- **Horaires :** 19h00 – 1h00 (spectacle à programmer dans cette tranche)
- **Nombre de personnes :** 230
- **Prestation :** spectacle de danse Bollywood

Prestataire : **Praful, Gérant de Parvati India** — contact@parvati-india.fr — 06 31 32 10 53

La page `index.html` permet de sélectionner une formule de spectacle avec uniquement des nombres
pairs de danseuses, avec calcul automatique du total transport inclus.
Adaptée à partir du modèle de la Nuit des Bibliothèques.

## Configuration de l'envoi d'e-mail

Le bouton du formulaire envoie maintenant la demande via `send-mail.php` en SMTP Hostinger. Le mot de passe ne doit pas être versionné.

1. Copier `smtp-config.example.php` vers `smtp-config.php` sur le serveur.
2. Remplacer `REMPLACER_PAR_LE_NOUVEAU_MOT_DE_PASSE_HOSTINGER` par le nouveau mot de passe de `contact@parvati-india.fr`.
3. Vérifier que `smtp-config.php` reste absent de Git : il est ignoré par `.gitignore`.

Paramètres utilisés par défaut :

- SMTP : `smtp.hostinger.com`
- Port : `465`
- Sécurité : SSL/TLS
- Identifiant / expéditeur / destinataire : `contact@parvati-india.fr`
