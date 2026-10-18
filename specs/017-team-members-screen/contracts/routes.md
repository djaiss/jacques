# Contract: Routes

Settings group (`auth`, `set.locale`, `welcome.screen`). `{staff}` binds by id with `whereNumber`;
a staff member, role or account of another company is a 404, covered by a test. Every route needs
`RoleManage` for the company, except account removal, which `DestroyUser` limits to owners (R5).

| Verb and URL | Name | Controller@method | Action |
| --- | --- | --- | --- |
| GET `settings/administration/team` | `settings.team.index` | `TeamController@index` | none; Everybody tab |
| GET `settings/administration/team/signs-in` | `settings.teamSignsIn.index` | `TeamSignsInController@index` | none; Signs in tab |
| GET `settings/administration/team/invited` | `settings.teamInvited.index` | `TeamInvitedController@index` | none; Invited tab |
| GET `settings/administration/team/no-account` | `settings.teamNoAccount.index` | `TeamNoAccountController@index` | none; No account tab |
| GET `settings/administration/team/{staff}` | `settings.team.show` | `TeamController@show` | none; list with the panel open (R1) |
| PUT `settings/administration/team/{staff}/role` | `settings.teamRoles.update` | `TeamRoleController@update` | `AssignRole` (account) or `PrepareStaffInvitation` (invitation), `role_id` (R4) |
| POST `settings/administration/team/{staff}/invitation` | `settings.staffInvitations.create` | `StaffInvitationController@create` | `PrepareStaffInvitation`, `role_id` |
| DELETE `settings/administration/team/{staff}/invitation` | `settings.staffInvitations.destroy` | `StaffInvitationController@destroy` | `DestroyStaffInvitation`, after confirmation |
| POST `settings/administration/team/{staff}/invitation/sending` | `settings.staffInvitationSendings.create` | `StaffInvitationSendingController@create` | `SendStaffInvitation`, optional `role_id` |
| DELETE `settings/administration/team/{staff}/account` | `settings.teamAccounts.destroy` | `TeamAccountController@destroy` | `DestroyUser`, after confirmation |

Cancel deletes the invitation in every state, so its link stops working; the revocation route,
`StaffInvitationRevocationController` and its test are removed (no screen reaches them any more).
Save changes shows only when an account or an invitation exists; otherwise the role goes with the
invitation buttons (R4).

## Responses

- Every mutation redirects to `settings.team.show` of the person with a `status` flash, except
  `TeamRoleController@update`, which redirects to `settings.team.index` with
  "The access of :name is saved." (spec US3).
- A refusal (state, send limit, last owner) comes back as a validation error under `panel`, shown
  inside the panel.
- Account removal redirects to `settings.team.show`, where the person now reads No account.

## Panel content, per person (`TeamViewModel::panel()`)

| Key | Content |
| --- | --- |
| `account` | state, label, sentence, and the actions of spec US2 with their url, method and confirmation |
| `roles` | each company role: id, name, description, selected, disabled (last owner) |
| `permissions` | per role id: groups of `{label, scope}` read only, and the role editor url |
| `isSoleOwner` | true when the person is the last active owner |
