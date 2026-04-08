# Neutraliser l'installateur

Apres installation:
- verifier le lock install actif
- restreindre/retirer acces public a `/install`
- conserver les fichiers install uniquement pour maintenance interne

Option simple Apache:
- ajouter regle deny sur `/install` en production
