# Contract: Routes and email

A staff member, invitation or role of another company is a 404, covered by a test. `{staff}` binds
by id with `whereNumber`. Every Settings mutation redirects to `settings.team.index` with a
`status` flash; a refusal (state, limit, onboarding pending) comes back as a validation error.

## Public invitation page (`routes/web.php`, `set.locale` only, `throttle:6,1`, token `[A-Za-z0-9]{64}`)

| Verb and URL | Name | Controller@method | Action |
| --- | --- | --- | --- |
| GET `invitations/{token}` | `invitations.show` | `InvitationController@show` | none; one of the R8 views, or the error view naming the case |
| POST `invitations/{token}` | `invitations.create` | `InvitationController@create` | `AcceptStaffInvitation`, sign in, redirect to `dashboard.index` with the welcome flash |
| POST `invitations/{token}/sign-out` | `invitations.signOut.create` | `InvitationSignOutController@create` | sign out, redirect to `invitations.show` |

"Sign in instead" links to `auth.signIn.new` after `redirect()->setIntendedUrl()` to the invitation.
Controllers live in `App\Http\Controllers\App\Invitation`.

## Settings, Team members (`auth`, `set.locale`, `welcome.screen`)

| Verb and URL | Name | Controller@method | Action |
| --- | --- | --- | --- |
| GET `settings/administration/team` | `settings.team.index` | `TeamController@index` | none; `RoleManage` for the company, like every action |
| POST `settings/administration/team/{staff}/invitation` | `settings.staffInvitations.create` | `StaffInvitationController@create` | `PrepareStaffInvitation` (`role_id`) |
| DELETE `settings/administration/team/{staff}/invitation` | `settings.staffInvitations.destroy` | `StaffInvitationController@destroy` | `DestroyStaffInvitation`, after confirmation |
| POST `settings/administration/team/{staff}/invitation/sending` | `settings.staffInvitationSendings.create` | `StaffInvitationSendingController@create` | `SendStaffInvitation` |
| POST `settings/administration/team/{staff}/invitation/revocation` | `settings.staffInvitationRevocations.create` | `StaffInvitationRevocationController@create` | `RevokeStaffInvitation`, after confirmation |

Each row shows only the actions of its state (spec US2). The screen and its actions need `RoleManage`. "Manage access" links to `settings.roles.show` of the role.

## Onboarding completion (`auth`, `set.locale`, `welcome.screen`, no step gate)

| Verb and URL | Name | Controller@method | Action |
| --- | --- | --- | --- |
| GET `onboarding/completion` | `onboarding.completion.index` | `CompletionController@index` | none; 404 while onboarding is pending |
| POST `onboarding/completion/invitations` | `onboarding.completion.invitations.create` | `CompletionInvitationController@create` | `SendStaffInvitation` per Prepared invitation |

`onboarding.availability.update` redirects to `onboarding.completion.index` instead of the dashboard.

## Email `StaffInvitationMail`

| Part | Content |
| --- | --- |
| Subject | ":inviter has invited you to join :salon" |
| Body | greeting with the first name, inviter and salon, one sentence on Jacques, Salon and Your role as a list, the button "Accept invitation", "This invitation expires in :count days" through `trans_choice()`, the ignore sentence |
| Recipient | `staff.email`, through `SendEmail` with `EmailTypeEnum::StaffInvitation`, queue `high` |
