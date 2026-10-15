# Décisions de conception

Ce document enregistre les décisions prises pour la première version de Jacques.
Il répond aux questions laissées ouvertes par `purpose.md`. Une spécification qui
le contredit a tort.

Les décisions sont temporaires. Quand l'une d'elles se révèle fausse au contact
d'un vrai salon, on la change ici et on note son annulation en fin de document.

## Principes

Jacques suit les principes de Getting Real. Trois d'entre eux gouvernent ce
document.

Construire moins. À chaque fois qu'une fonction peut attendre un client qui la
réclame, elle attend.

Décider à la place de l'utilisateur. Un réglage offert au salon est une décision
que Jacques refuse de prendre.

Résoudre par l'humain plutôt que par la règle. Quand une contrainte peut être
gérée par la personne devant l'écran, Jacques l'avertit et la laisse faire.

## L'idée principale

Le salon voit sa journée, et les clientes réservent en ligne toutes seules.

Ce qui distingue Jacques d'un agenda générique est le temps de pose. Une
prestation de coiffure n'est pas un bloc plein : pendant une pose, la personne
redevient disponible pour quelqu'un d'autre. Tout le reste de la v1 sert cette
idée ou l'accompagne.

## Le salon visé

Un salon indépendant de trois à huit personnes, sur une ou plusieurs adresses.

Assez grand pour que l'agenda soit un vrai problème et que les rôles servent.
Assez petit pour que les ressources et le plan de charge ne manquent à
personne. C'est ce profil qui justifie les coupes ci dessous, et
c'est à lui qu'il faut revenir quand une décision hésite.

## Structure du salon

### D1. Un salon peut avoir plusieurs établissements

Chaque établissement a son adresse, ses horaires d'ouverture et ses jours de
fermeture.

Une personne est affectée à un ou plusieurs établissements. Un responsable
n'agit que sur les établissements auxquels il est affecté (D4).

### D2. Le salon déclare sa langue, son fuseau horaire et ses devises

Les trois sont posés à la création du salon et s'appliquent partout : le back
office, la page publique de réservation et les messages envoyés.

Le fuseau horaire est la valeur qui compte le plus. Toutes les heures sont
stockées en temps universel et affichées dans le fuseau du salon. Les devises ne
servent qu'à afficher les prix du catalogue, puisque Jacques n'encaisse rien
(D22). Un salon peut en déclarer plusieurs : chaque prestation porte alors un
prix dans chacune, saisi par le salon. Jacques ne convertit jamais un montant
d'une devise à une autre.

### D3. Un membre de l'équipe est une fiche, liée ou non à un compte

Un membre de l'équipe (`Staff`) existe sans compte. Une personne qui ne se
connecte jamais figure quand même au planning. Une fiche est liée à un compte au
plus, et un compte à une fiche au plus.

Conséquence : une personne qui quitte le salon est désactivée. Sa fiche ne peut
être supprimée que tant qu'aucun rendez-vous ne la mentionne, sinon l'historique
des rendez-vous perd son auteur.

### D4. Quatre rôles par défaut, modifiables et cumulables

Chaque salon démarre avec quatre rôles : propriétaire, responsable, personnel,
réceptionniste. Le salon peut les modifier et en créer d'autres depuis l'écran
d'administration des droits, qui reste en place.

Une personne en porte plusieurs. Dans un salon de trois à huit personnes, la
propriétaire coiffe, et la réceptionniste prend les rendez-vous.

Le rôle propriétaire donne tous les droits et seul lui distribue les rôles. Un
salon garde toujours au moins un propriétaire actif.

## Prestations

### D5. Une prestation est une suite d'étapes, avec temps de pose

Une étape porte une durée active, une durée de pose pendant laquelle la personne
redevient disponible, et son rang dans la prestation.

C'est l'idée principale du produit. Tout le reste peut sauter avant elle.

### D6. Une seule personne réalise tout le rendez-vous

Même si la prestation a trois étapes, c'est la même personne du début à la fin.
Le temps de pose la libère pour une autre cliente, mais il ne permet pas de
confier une étape à quelqu'un d'autre.

L'affectation étape par étape attend un salon qui la réclame.

### D7. Un rendez-vous peut contenir plusieurs prestations

Une coupe suivie d'une couleur est un seul rendez-vous. Les prestations
s'enchaînent dans l'ordre choisi, et leurs étapes forment une seule suite. Une
pose au milieu libère la personne, quelle que soit la prestation qui la contient.

### D8. Le prix est surchargeable par établissement, la durée non

Une prestation n'est proposée dans un établissement qu'après y avoir été
ajoutée, et chaque établissement peut y porter son propre prix. En l'absence de
surcharge, le prix de la prestation s'applique.

Les durées, elles, sont celles de la prestation partout et pour tout le monde. Surcharger
une durée n'a pas de sens tant qu'une prestation est une suite d'étapes : il
faudrait dire quelle étape est rallongée, et le temps de pose est imposé par le
produit, pas par la personne.

Conséquence acceptée : Jacques propose les mêmes créneaux pour une apprentie et
pour la directrice artistique, donc la fin de journée décale quand la plus lente
prend le rendez-vous.

### D9. Il n'y a pas de ressources

Bacs, fauteuils et cabines ne limitent rien. Le salon les gère comme
aujourd'hui, de tête.

## Agenda et réservation

### D10. Une réservation en ligne est acceptée sans validation du salon

Le créneau proposé est réel. Le rendez-vous existe dès que la cliente valide, et
personne au salon n'a rien à approuver.

Accepter une réservation et confirmer une présence sont deux choses distinctes.
Celle ci est immédiate et automatique. L'autre vient plus tard et dépend de la
cliente (D12).

Conséquence : aucun filet humain ne rattrape une erreur de calcul, donc le
moteur de disponibilité doit être juste et protégé contre deux réservations
simultanées du même créneau.

### D11. La cliente choisit une personne ou dit sans préférence

Sans préférence, la recherche balaie toute l'équipe qualifiée et retient la
première personne disponible. À égalité, un ordre stable départage, pour que la
même recherche donne toujours le même résultat.

Reproposer la coiffeuse habituelle supposerait d'identifier la cliente avant de
lui montrer des disponibilités, ce que la page publique ne sait pas faire. La
coiffeuse habituelle est donc préremplie uniquement quand la cliente arrive par
un lien de reprise de rendez-vous, où elle est déjà connue.

### D12. Un rendez-vous a quatre statuts

Prévu, présence confirmée, annulé, non honoré. Un rendez-vous passé et non
annulé est honoré sans que personne ne clique. Seuls le lapin et la confirmation
de présence demandent un geste.

Le nom du statut dit exactement ce qu'il couvre. La cliente confirme qu'elle
viendra, elle ne confirme pas sa réservation, qui était acquise au moment de la
prise (D10).

La présence se confirme par un bouton sur la page que le lien sécurisé du rappel
ouvre déjà. Jacques n'a donc pas besoin de recevoir des SMS, seulement d'en
envoyer.

C'est la seule arme contre les rendez-vous non honorés, puisque Jacques ne prend
pas d'acompte (D22).

### D13. Une absence de confirmation ne déclenche rien

Le rendez-vous reste prévu, l'agenda montre que la présence n'est pas confirmée,
et un écran regroupe les rendez-vous concernés des prochains jours pour que le
salon les traite d'un coup.

Jacques n'annule jamais tout seul. Annuler une cliente qui comptait venir mais
n'a pas vu le message est exactement la décision opérationnelle qu'une
automatisation ne doit pas prendre à la place du salon.

### D14. La fenêtre de réservation est réglable

Par défaut, une cliente réserve jusqu'à trois mois à l'avance et jusqu'à une
heure avant le rendez-vous. Le salon peut changer ces deux bornes.

Trois mois couvrent le cycle de six semaines d'une coloration. Une heure garde le
remplissage de dernière minute, qui est ce que la réservation en ligne fait de
mieux.

Cette décision et la D20 créent une page de réglages, contre « Avoid
Preferences ». Les besoins diffèrent réellement d'un salon à l'autre sur ces
deux points, et nulle part ailleurs.

### D15. Le back office peut forcer un rendez-vous interdit

Le salon voit ce qui ne va pas (personne déjà prise, hors horaires) et confirme
en connaissance de cause.

Conséquence : l'agenda doit savoir afficher des chevauchements, et le conflit
reste visible après coup.

### D16. Il n'y a pas de rendez-vous récurrents

Jacques n'inscrit rien à l'avance. Il enregistre la fréquence habituelle de la
cliente et envoie un message quand il est temps de reprendre rendez-vous.

### D17. Il n'y a pas de liste d'attente

## Clients

### D18. Un numéro de téléphone correspond à une seule fiche cliente

L'adresse email est facultative. Deux réservations portant le même numéro
rejoignent la même fiche, quel que soit le nom saisi.

Conséquences : les numéros sont normalisés au format international avant toute
comparaison, le SMS devient le canal obligatoire du lien sécurisé, et un
fournisseur de SMS est nécessaire dès le premier jour.

Conséquence acceptée : une mère qui réserve pour sa fille depuis son propre
portable pose le rendez-vous sur sa propre fiche. Les notes et les formules de
coloration des deux personnes se retrouvent au même endroit, et le salon devra
s'en arranger dans les notes.

### D19. Il n'y a pas de photos de clientes

Les notes et les formules de coloration couvrent le besoin. Le stockage de
photos de personnes identifiables attend que le produit ait de vrais clients et
une politique de conservation écrite.

### D20. Le lien sécurisé permet d'annuler et de déplacer

Les deux actions sont possibles jusqu'à deux heures avant le rendez-vous, délai
que le salon peut changer. Il n'y a pas de plafond au nombre de déplacements.

Être permissif vaut mieux qu'être strict. Une cliente qui ne peut pas annuler la
veille au soir appelle, ou ne vient pas du tout, et le salon n'encaisse rien
qu'un délai serré protégerait. Le plafond de déplacements attend un salon qui
constate l'abus.

### D21. Un lien sécurisé meurt avec son rendez-vous

Le lien parle d'un rendez-vous précis et cesse de fonctionner dès que celui ci
est passé, annulé ou non honoré. Chaque message envoyé en porte un neuf.

Conséquence : la relance de reprise de rendez-vous (D16) ne peut pas réutiliser
l'ancien lien et porte le sien.

### D22. Jacques ne touche pas à l'argent

Aucun encaissement, aucun acompte, aucune caisse, aucun enregistrement de ce qui
a été payé. Le salon a déjà une caisse et Jacques ne la remplace pas.

Conséquences : la politique d'annulation est une phrase affichée sans moyen
d'être appliquée, et la seule protection contre un rendez-vous non honoré est la
confirmation de présence demandée à la cliente (D12).

### D23. Une demande d'effacement anonymise la fiche

Nom, téléphone, email, notes et formules sont effacés. Les rendez-vous restent,
rattachés à une fiche vide, pour que le salon garde son historique d'activité.

## Produits et modèle commercial

### D24. Il n'y a ni produits, ni stocks, ni fournisseurs

### D25. Jacques est facturé à prix unique par salon

Un tarif mensuel, quel que soit l'effectif. Ni commission sur les rendez-vous, ni
prix par personne.

La commission est ce que font les plateformes contre lesquelles Jacques se
définit, donc elle est exclue. Le prix par personne pousserait le salon à ne pas
inscrire tout le monde, et un agenda auquel il manque des gens ne sert à rien.

## Plateforme

### D26. Le back office et l'API appellent le même noyau

Les contrôleurs web et les contrôleurs API appellent les mêmes Actions. Une règle
métier n'existe qu'une fois. Le back office reste en Blade, Tailwind et Alpine
Ajax, et ne passe pas par HTTP sur l'API publique.

Risque assumé : rien ne garantit techniquement que l'API couvre toutes les
opérations. La promesse est tenue à la main et se dégradera.

### D27. L'API publique fait partie de la v1

API, contrat OpenAPI, webhooks, MCP et jetons d'API sont livrés avec la v1.

Cette décision contredit « Scale Later ». Elle est prise sciemment, parce que
l'ouverture est la position de Jacques face aux plateformes qui enferment le
salon, et pas une fonction parmi d'autres.

### D28. Il n'y a ni import, ni export, ni journal d'audit

Les premiers salons sont migrés à la main, ce qui apprendra ce que contiennent
vraiment leurs données.

### D29. Français, anglais et espagnol au lancement

Traduire une interface coûte quelques minutes. La commande `jacques:localize`,
la skill de traduction et les règles de pluriel existent déjà, et ces dernières
sont nécessaires même avec le français seul. Le nombre de locales n'est donc pas
un sujet.

Ce qui coûte est ailleurs et n'est pas couvert par cette décision : répondre au
support dans une langue qu'on ne parle pas, et écrire les conditions générales,
les mentions RGPD et la page de vente de chaque marché.

### D30. La page publique de réservation parle la langue du salon

Elle ne suit pas le navigateur de la cliente. Une page en allemand dans un salon
parisien où personne ne parle allemand promet un accueil que le salon ne peut
pas tenir.

### D31. Une réservation publique exige de vérifier le numéro de téléphone

Après avoir choisi son créneau et saisi ses coordonnées, la cliente reçoit un
code à six chiffres par SMS. Le rendez-vous n'existe qu'une fois ce code validé.

Le code expire avec le créneau temporairement réservé. Sa validation transforme
le créneau en rendez-vous de façon atomique : il est impossible de valider le
code et de perdre néanmoins le créneau au profit d'une autre cliente.

Un lien sécurisé de reprise de rendez-vous prouve déjà que la cliente contrôle
le numéro auquel il a été envoyé. Dans ce cas, Jacques ne redemande pas de code.

Cette étape ajoute une friction volontaire. Puisque le salon ne valide pas les
réservations et que Jacques ne prend pas d'acompte, elle évite au moins les faux
numéros et les erreurs de saisie.

## Ce que ces décisions impliquent comme modèles

Les vingt fichiers de concepts effacés listaient 99 modèles. Les décisions ci
dessus en laissent 25.

### Salon et équipe

`Salon`, `User`, `Staff`, `LocationStaff`, `StaffWorkingHour`, `StaffAbsence`.

Le salon porte sa langue, son fuseau horaire, ses devises et les deux réglages de
réservation (D14, D20).

`LocationStaff` affecte une fiche à un établissement, et peut être suspendue.

`StaffWorkingHour` est une plage horaire hebdomadaire d'une personne dans un
établissement. Plusieurs plages le même jour laissent une pause entre elles.

`StaffAbsence` est une période datée, en UTC, où une personne ne travaille pas,
dans un établissement ou dans tous. Les horaires d'ouverture et les jours de
fermeture des établissements restent à modéliser.

### Catalogue

`ServiceCategory`, `Service`, `ServiceStep`, `LocationService`,
`StaffLocationService`.

`LocationService` dit qu'un établissement propose une prestation, et porte le
prix surchargé (D8). Savoir faire une prestation dans un établissement, c'est
figurer dans `StaffLocationService`. Il n'y a pas de modèle de compétence et
aucune durée n'est surchargée.

Une variante de prestation se déclare comme une prestation distincte dans la
même catégorie. Il n'y a ni variante ni forfait.

### Agenda

`Appointment`, `AppointmentService`, `AppointmentStep`, `AvailabilityHold`.

`AppointmentService` porte les prestations d'un même rendez-vous et leur ordre
(D7).

`AppointmentStep` porte les heures réelles de chaque étape et dit si la personne
est occupée ou en pose. Sans lui, savoir qui est libre à quatorze heures oblige
à recalculer chaque rendez-vous de la journée.

`AvailabilityHold` réserve un créneau le temps que la cliente remplisse le
formulaire et vérifie son téléphone, pour que deux personnes ne prennent pas le
même. Le défi de vérification est temporaire et ne crée pas de fiche cliente :
la fiche et le rendez-vous sont créés ensemble après validation du code (D31).

### Clients

`Client`, `ClientNote`, `ColorFormula`, `Tag` et son pivot.

Les préférences sont des colonnes de la fiche. Le numéro de téléphone y est
unique (D18).

### Portail client

`PortalAccessLink`, qui peut réutiliser le `MagicLink` existant. Il porte le
rendez-vous dont il parle et meurt avec lui (D21).

Il n'y a pas de compte client en v1, seulement le lien.

### Communications

`MessageTemplate`, `Communication`, `Consent`.

L'état de livraison est une colonne de `Communication`. Il n'y a ni campagne ni
automatisation configurable, et aucun message entrant à traiter (D12).

### Plateforme

`WebhookEndpoint`, `WebhookDelivery`.

Les jetons d'API réutilisent `personal_access_tokens`, déjà présent. La version
d'API est un préfixe de route et les portées sont des constantes, pas des
modèles.

## Décisions annulées

Prises le 11 septembre 2026, annulées le 12 septembre 2026 après confrontation
aux principes de Getting Real.

- Plusieurs établissements dès la v1, et mobilité des personnes entre eux.
- Affectation d'une étape à une personne différente du reste du rendez-vous.
- Ressources contraignantes pour la réservation.
- Prix déterminé par l'étape principale, devenu sans objet puisqu'une seule
  personne réalise tout le rendez-vous.
- Produits, stocks par établissement, transferts, consommation automatique
  corrigeable et réapprovisionnement fournisseur.
- Enregistrement de ce qui a été payé.
- Import CSV, export complet et journal d'audit.

Annulées le 12 septembre 2026 après relecture.

- Surcharge de la durée d'une prestation par personne, jamais définie face aux
  étapes et au temps de pose.
- Confirmation de présence par réponse à un SMS entrant, remplacée par un bouton
  sur la page du lien sécurisé.
- Coiffeuse habituelle reproposée sur la page publique, impossible puisque la
  cliente n'y est pas identifiée avant de voir les disponibilités.
- Survie du lien sécurisé quelques jours après le rendez-vous.

Annulées le 14 octobre 2026, à la définition des rôles par défaut.

- Un seul établissement en v1 (ancienne D1), remplacée parce qu'un responsable
  est affecté à des établissements précis.
- Rôles fixes et sans écran d'administration des droits (ancienne D4),
  remplacée parce que l'éditeur de rôles existant est conservé.

Annulée le 14 octobre 2026, à la définition du catalogue.

- Prix surchargé par personne (ancienne D8), remplacé par un prix surchargé par
  établissement, là où la prestation est proposée.

## Questions encore ouvertes

Aucune ne bloque l'écriture des spécifications détaillées.

1. Les passages autorisés entre statuts. Un rendez-vous annulé peut il revenir à
   prévu, un non honoré peut il être corrigé le lendemain.
2. Le fournisseur de SMS, qui n'a plus qu'à envoyer.
3. La durée de conservation d'une fiche cliente sans rendez-vous, au delà de
   l'effacement sur demande de la D23.
4. Le montant de l'abonnement et l'existence d'un essai gratuit.
5. L'ordre stable qui départage deux personnes également disponibles (D11).

## Risques assumés

Aucune date de livraison n'est fixée. Getting Real tient « Fix Time and Budget,
Flex Scope » pour le mécanisme qui force les arbitrages. Sans lui, le périmètre
ne se coupe pas tout seul, et cette séance de décisions vient de le montrer.

Aucun salon partenaire n'est engagé. Les décisions ci dessus reposent donc sur
des hypothèses sur un métier que personne dans l'équipe ne pratique. Les
premières corrections viendront tard, au contact.
