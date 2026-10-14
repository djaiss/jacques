# Jacques — raison d’être du produit

## Statut du document

Ce document décrit le concept de Jacques. Jacques n’est plus un outil de gestion
des employés généraliste : il devient un logiciel de gestion destiné aux salons
de coiffure.

Celui ci porte la vision. `decisions.md` enregistre les décisions de conception
et fait foi. `v1.md` fixe le périmètre de la première version.
`booking-flow.md` décrit le parcours critique de réservation côté client. Les
autres spécifications détaillées restent à écrire.

## Le concept

Jacques est un logiciel qui aide un salon de coiffure à faire fonctionner son
activité quotidienne et permet à ses clients de réserver et gérer leurs
rendez-vous simplement.

Le produit réunit dans un même système :

- l’agenda et les rendez-vous ;
- les clients et leur historique ;
- les prestations et les membres de l’équipe capables de les réaliser ;
- les produits, le stock et les fournisseurs ;
- les paiements ;
- les communications avec les clients ;
- le suivi de l’activité du salon.

L’objectif n’est pas seulement de proposer un calendrier. Jacques doit devenir
le système opérationnel du salon : la source commune utilisée pour savoir qui
vient, pour quelle prestation, avec quel membre de l’équipe, pendant combien de
temps, à quel prix et avec quelles ressources.

## Les utilisateurs

Jacques sert deux groupes distincts.

### Les professionnels du salon

Les propriétaires, responsables, réceptionnistes et coiffeurs utilisent le Back
Office pour organiser le travail, suivre les clients et piloter l’activité.

Le Back Office répond à la question :

> Comment faisons-nous fonctionner le salon aujourd’hui et dans les semaines à
> venir ?

### Les clients du salon

Les clients utilisent le Portail client pour réserver, consulter, déplacer ou
annuler un rendez-vous, payer lorsque nécessaire et reprendre rendez-vous.

Le Portail client répond à la question :

> Comment puis-je interagir facilement avec mon salon ?

La création d’un compte client doit rester optionnelle. Un client doit pouvoir
réserver sans compte et gérer un rendez-vous depuis un lien personnel sécurisé
reçu par email, SMS ou WhatsApp.

## Une plateforme, deux expériences

Le Back Office et le Portail client sont les deux expériences visibles d’un même
produit. Ils reposent sur le même cœur métier et manipulent les mêmes données en
temps réel.

```text
                         JACQUES
                            │
                       CŒUR SALON
                            │
             ┌──────────────┴──────────────┐
             │                             │
        BACK OFFICE                  PORTAIL CLIENT
             │                             │
    Agenda et équipe                 Réservation
    Clients et services              Rendez-vous
    Stock et fournisseurs            Paiement
    Paiements et reporting           Liste d’attente
             │                             │
             └──────────────┬──────────────┘
                            │
                   COMMUNICATIONS
                  Email, SMS, WhatsApp
                            │
                    API ET INTÉGRATIONS
```

Une modification faite dans une expérience doit immédiatement être vraie dans
l’autre. Par exemple, déplacer un rendez-vous depuis le Portail client met à
jour l’agenda du salon ; bloquer une absence dans le Back Office retire les
créneaux concernés de la réservation en ligne.

## Le cœur métier

Le cœur de Jacques doit notamment pouvoir représenter :

- un ou plusieurs établissements appartenant au même salon ;
- les horaires, absences, compétences et permissions de l’équipe ;
- les prestations, leurs variantes, leurs prix et leurs étapes ;
- les temps actifs, temps de pose, pauses et marges avant ou après une
  prestation ;
- les ressources nécessaires à une prestation ;
- les disponibilités calculées à partir de l’ensemble de ces contraintes ;
- les rendez-vous, leur état et leur historique ;
- les clients, leurs préférences, notes, photos et historique ;
- les produits utilisés pendant une prestation ou vendus au client ;
- les mouvements de stock et les commandes fournisseurs ;
- les paiements, acomptes, pourboires et remboursements ;
- les consentements et communications envoyées au client.

Ce modèle commun doit être exposé par une API afin que l’interface web, le
Portail client, les automatisations et les intégrations ne développent pas des
règles métier contradictoires.

## Principes produit

### Conçu pour la réalité d’un salon

L’agenda doit comprendre le métier de la coiffure. Une prestation peut avoir
plusieurs étapes, mobiliser différentes personnes ou ressources et contenir un
temps de pose pendant lequel le coiffeur redevient disponible.

### Simple dans les cas courants

Les opérations répétées toute la journée — créer, déplacer, confirmer, commencer
ou terminer un rendez-vous — doivent être rapides. La richesse du modèle ne doit
pas rendre les actions ordinaires compliquées.

### Sans friction pour le client

La réservation et la gestion d’un rendez-vous ne doivent pas imposer la création
d’un compte. Les messages servent principalement de porte d’entrée vers une page
sécurisée permettant d’agir immédiatement.

### Une seule source de vérité

Disponibilités, prix, clients, rendez-vous, paiements et stocks ne doivent pas
être maintenus séparément selon le canal. Toutes les expériences utilisent les
mêmes règles et les mêmes données.

### L’humain garde le contrôle

Jacques peut proposer, rappeler et faciliter, mais une automatisation ne doit pas
prendre silencieusement une décision opérationnelle importante à la place du
salon ou du client.

### Ouvert aux intégrations

Une API documentée, des webhooks et des permissions précises font partie de la
plateforme. Les intégrations doivent utiliser les mêmes opérations métier que les
interfaces officielles.

## Périmètre fonctionnel envisagé

Le produit pourra couvrir les domaines suivants, sans préjuger de leur ordre de
livraison :

1. agenda, disponibilités, rendez-vous et liste d’attente ;
2. clients et historique relationnel ;
3. catalogue de prestations ;
4. équipe, horaires, absences, compétences et commissions ;
5. produits, stocks et fournisseurs ;
6. encaissement, acomptes, pourboires et remboursements ;
7. confirmations, rappels, relances et campagnes simples ;
8. indicateurs opérationnels et financiers ;
9. réservation et gestion des rendez-vous côté client ;
10. API, webhooks, OAuth, clés d’API et MCP.

Cette liste exprime la vision du produit, pas le périmètre de la première
version. Ce qui entre dans la v1 est dans `v1.md`.

## Ce que Jacques n’est pas

Jacques n’est pas :

- un simple calendrier générique auquel on ajoute le mot « salon » ;
- une place de marché destinée à mettre en concurrence les salons ;
- un réseau social entre salons et clients ;
- un compte client obligatoire avant toute réservation ;
- un ensemble de produits séparés possédant chacun leur propre copie des
  clients, rendez-vous ou disponibilités.

## Questions à trancher

La plupart des questions ouvertes par ce document sont tranchées dans
`decisions.md`. Celles qui restent y figurent en fin de document, notamment le
type de salon ciblé en premier, sa taille typique et le modèle commercial.
