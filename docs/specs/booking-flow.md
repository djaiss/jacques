# Parcours de réservation côté client

## Statut du document

Ce document décrit le parcours public qui permet à une cliente de réserver un
rendez-vous sans compte. Il précise l'expérience visible et les règles qui la
soutiennent. `decisions.md` fait foi en cas de contradiction.

Ce parcours est la partie la plus critique de Jacques. Il doit donner accès à la
puissance du moteur de disponibilité sans exposer sa complexité.

## Résultat attendu

Une cliente choisit ce qu'elle veut, trouve un créneau réel, vérifie son numéro
de téléphone et obtient immédiatement un rendez-vous accepté par le salon.

Le parcours nominal est :

```text
Prestations → Personne → Créneau → Coordonnées → Code SMS → Rendez-vous réservé
```

Il n'y a ni compte, ni mot de passe, ni validation ultérieure par le salon.

## Principes d'interface

- une seule décision principale par écran ;
- un seul bouton principal visible ;
- les choix précédents restent présents quand la cliente revient en arrière ;
- la cliente voit la durée totale, l'heure de fin et le prix, jamais les étapes
  internes ni les temps de pose ;
- Jacques ne demande aucune information avant qu'elle soit nécessaire ;
- un échec tardif ne fait pas recommencer le parcours ;
- le parcours est conçu d'abord pour un téléphone mobile.

Une étape logique peut tenir sur le même écran qu'une autre si cela réduit
réellement l'effort. La structure ci-dessous décrit les décisions successives,
pas l'obligation de construire six pages distinctes.

## Entrées dans le parcours

### Page publique du salon

La cliente arrive sur l'adresse publique du salon. Elle voit son nom, son
adresse et l'action « Prendre rendez-vous ». La page utilise la langue, le
fuseau horaire et la devise du salon (D2, D30).

La réservation commence sans identifier la cliente.

### Lien de reprise de rendez-vous

Un message de reprise peut ouvrir le même parcours avec les prestations du
dernier rendez-vous et la personne habituelle déjà sélectionnées. La cliente
arrive directement au choix du créneau, mais peut modifier tous les choix.

Ce lien prouve déjà le contrôle du numéro auquel il a été envoyé. Le parcours ne
redemande donc pas de code SMS (D11, D16, D31).

## Étape 1 — Choisir les prestations

Les prestations sont regroupées par catégorie. Chacune affiche :

- son nom ;
- sa durée totale ;
- son prix de base, précédé de « À partir de » lorsqu'une personne pratique un
  prix différent.

La cliente choisit une ou plusieurs prestations. Elles seront réalisées par la
même personne et s'enchaîneront dans l'ordre de sélection (D6, D7).

Le récapitulatif visible en permanence affiche les prestations sélectionnées,
leur durée totale et le prix encore provisoire.

Si aucune personne ne peut réaliser l'ensemble choisi, Jacques l'explique
immédiatement et demande de retirer une prestation. Il ne conduit pas la
cliente jusqu'au calendrier pour lui annoncer ensuite qu'aucun créneau
n'existe.

## Étape 2 — Choisir une personne

Jacques ne présente que les personnes capables de réaliser toutes les
prestations choisies.

La première option est « Sans préférence ». Les autres options portent le nom
de la personne et le prix total exact avec elle.

Choisir « Sans préférence » ne sélectionne pas encore une personne. Pour chaque
heure proposée à l'étape suivante, Jacques retiendra la première personne
disponible selon l'ordre stable défini par D11.

## Étape 3 — Choisir un créneau

Jacques montre d'abord les prochains jours qui possèdent au moins un créneau. La
cliente peut ensuite ouvrir le calendrier pour aller à une autre date dans la
fenêtre autorisée par le salon (D14).

Chaque proposition affiche :

- l'heure de début et l'heure de fin ;
- la personne qui réalisera le rendez-vous ;
- le prix total exact.

Le moteur peut exploiter un temps de pose pour proposer le créneau, mais ne
l'explique pas à la cliente. De son point de vue, le rendez-vous reste continu
du début à la fin.

Lorsqu'elle choisit un créneau, Jacques crée un `AvailabilityHold` de dix
minutes couvrant tous les intervalles actifs du rendez-vous. Pendant ce délai,
le créneau n'est plus proposé à quelqu'un d'autre.

Le délai restant n'est affiché que pendant les deux dernières minutes, afin de
ne pas ajouter de pression inutile.

## Étape 4 — Saisir les coordonnées

Jacques demande :

- le nom ;
- le numéro de téléphone ;
- l'adresse email, facultative.

Le numéro est normalisé au format international. L'écran récapitule aussi les
prestations, la personne, la date, les heures, le prix et la politique
d'annulation du salon.

La saisie d'un numéro déjà connu ne révèle aucune information sur la fiche ou
les rendez-vous existants. Le rapprochement avec la fiche cliente a lieu en
interne conformément à D18.

L'action principale est « Recevoir mon code ».

## Étape 5 — Vérifier le téléphone

Jacques envoie un code à six chiffres par SMS au numéro saisi. L'écran montre le
numéro partiellement masqué, permet de le corriger et accepte le code sans
demander d'autre information.

Le code et le `AvailabilityHold` expirent ensemble après dix minutes. Demander
un nouveau code ne prolonge pas le créneau.

L'action principale est « Confirmer le rendez-vous ». Quand le code est valide,
Jacques transforme atomiquement le créneau réservé en rendez-vous au statut
« prévu » (D10, D12, D31).

Une nouvelle soumission du même code après un succès retourne le même
rendez-vous. Elle n'en crée jamais un deuxième.

## Étape 6 — Confirmer la réussite

La page finale dit clairement que le rendez-vous est réservé. Elle affiche :

- le salon et son adresse ;
- les prestations ;
- la personne ;
- la date, l'heure de début et l'heure de fin ;
- le prix total ;
- la politique d'annulation.

Jacques envoie immédiatement un SMS contenant les mêmes informations et un lien
sécurisé vers le rendez-vous. Si une adresse email a été fournie, il envoie aussi
la confirmation par email.

Le lien permet de consulter, déplacer ou annuler le rendez-vous selon D20 et
D21. La réservation ne dépend pas de la bonne livraison de l'email après que le
numéro a été vérifié.

## Calcul des propositions

Un créneau est proposé seulement si :

- une même personne peut réaliser toutes les prestations ;
- chaque intervalle actif tient dans ses disponibilités ;
- aucun intervalle actif ne chevauche un autre rendez-vous ou un créneau
  temporairement réservé ;
- les étapes reviennent dans le bon ordre après chaque temps de pose ;
- le rendez-vous respecte les horaires et la fenêtre de réservation du salon.

Un temps de pose ne mobilise pas la personne. Un autre rendez-vous peut donc
occuper tout ou partie de ce temps, à condition que les étapes actives suivantes
des deux rendez-vous restent possibles.

Les bacs, fauteuils, cabines et autres ressources n'entrent pas dans le calcul
(D9).

## Absence de disponibilité

Si aucune heure n'est disponible, Jacques ne montre pas un calendrier vide. Il
propose, dans cet ordre :

1. voir les prochaines dates disponibles ;
2. revenir au choix de la personne ;
3. modifier les prestations ;
4. contacter directement le salon.

Il n'y a ni demande vague de disponibilité, ni liste d'attente (D17).

## Échecs pendant la réservation

### Le créneau expire

Jacques conserve les prestations, la personne et les coordonnées, puis ramène
la cliente aux disponibilités avec le message : « Ce créneau n'est plus
disponible. Choisissez-en un autre. »

### Le code est incorrect

Jacques l'indique sans effacer les autres données. Après cinq tentatives
incorrectes, le code devient invalide et la cliente doit en demander un nouveau.

### Le SMS n'arrive pas

La cliente peut demander un nouvel envoi après trente secondes ou corriger son
numéro. Le nouvel envoi ne prolonge pas le créneau. Si le délai expire, elle
choisit un nouveau créneau sans ressaisir ses coordonnées.

### Le créneau devient impossible au dernier instant

La création du rendez-vous revérifie la disponibilité dans la même opération.
En cas de conflit, aucun rendez-vous n'est créé. Jacques conserve les données et
ramène la cliente au choix du créneau.

## Ce que le parcours ne fait pas

- créer un compte client ;
- demander un mot de passe ;
- prendre un paiement ou un acompte ;
- demander au salon d'approuver la réservation ;
- afficher ou faire configurer les étapes techniques d'une prestation ;
- proposer une liste d'attente ;
- proposer plusieurs établissements ;
- suggérer automatiquement une autre prestation.

## Scénarios d'acceptation

### Première réservation

Étant donné une cliente arrivée sur la page publique,
quand elle choisit des prestations, une personne, un créneau, saisit ses
coordonnées et valide le code reçu,
alors un unique rendez-vous prévu est créé et son lien sécurisé lui est envoyé.

### Numéro non vérifié

Étant donné un créneau temporairement réservé,
quand la cliente ne valide pas son numéro avant l'expiration,
alors aucun rendez-vous ni aucune fiche cliente n'est créé et le créneau redevient
disponible.

### Réservation sans préférence

Étant donné plusieurs personnes capables de réaliser les prestations,
quand la cliente choisit « Sans préférence »,
alors chaque créneau affiché nomme la personne retenue et montre son prix exact.

### Temps de pose utilisé

Étant donné un rendez-vous existant comportant un temps de pose,
quand une autre prestation tient entièrement dans les intervalles où la personne
est libre,
alors Jacques peut proposer ce créneau sans montrer le temps de pose à la
cliente.

### Conflit simultané

Étant donné deux clientes qui choisissent le même dernier créneau,
quand la première obtient le créneau temporaire,
alors la seconde ne peut plus le sélectionner et un seul rendez-vous peut être
créé.

### Reprise de rendez-vous

Étant donné une cliente arrivée par un lien de reprise valide,
quand elle réserve de nouveau,
alors ses prestations et sa personne habituelle sont préremplies et aucun code
SMS supplémentaire n'est demandé.
