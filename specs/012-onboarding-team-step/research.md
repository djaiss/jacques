# Research: Onboarding Step 3, Your Team

## R1. A prepared invitation is its own record

- Decision: a new table `staff_invitations`, one row per staff member at most, holding the role it will grant and who prepared it. The address is the staff member's email, read when the invitation is sent, so it cannot drift from the record. Deleting the staff member deletes the row (foreign key cascade).
- `PrepareStaffInvitation` (new) creates the row or changes its role. `DestroyStaffInvitation` (new) removes it. Neither sends anything (spec FR-013).
- `UpdateStaffInformation` removes the invitation when the email becomes empty, wherever the edit comes from (spec edge case "no email means no invitation").
- Rejected: an `invited_role_id` column on `staff` (mixes a pending request into the profile, and the later sending feature needs its own dates and token anyway); a row in `users` without a password (an account that nobody created, which the sign in screens would then have to exclude).

## R2. The owner is on the team from the end of step 2

- Today the owner's staff record exists since sign up (`CreateCompany`) but is assigned to no location and performs nothing, so the design's first row ("All N services") would be false.
- Decision: `OfferServicesAtLocation` returns the location services it created. `CompleteOnboardingStep`, for the services step, then assigns the author's staff record to the headquarters (`AssignLocation`, which ignores an existing assignment) and lets it perform the services just offered.
- Step 1 (`SetUpSalon`) already offers the example catalogue at the headquarters, so the first time the owner joins it they receive every service offered there. On later runs only the services offered in that run are given, so a service the owner unticked for themselves in step 3 is not given back if they return to step 2 and continue again.
- Rejected: assigning the owner when step 3 opens (a write on a GET request).

## R3. Saving a team member is one form and a short series of actions

- Decision: the panel is one form. The controller runs, in a transaction: `CreateStaff` or `UpdateStaffInformation`, then `AssignLocation` to the headquarters for a new member, then `SyncStaffLocationServices`, then `PrepareStaffInvitation` or `DestroyStaffInvitation`.
- `SyncStaffLocationServices` (new) takes the staff member, a location and the location service ids wanted there; it adds and removes the difference and logs once (`StaffServicesUpdated`). Calling `AssignStaffLocationService` per service would log one line per checkbox.
- An unbookable member's checkboxes are hidden with `x-show`, not removed, so the selection is still submitted and kept (spec FR-009).
- Rejected: a single "save onboarding team member" action (it would duplicate the rules of four existing actions); autosave per field as in step 2 (the design has explicit "Add to the team" and "Save changes", and a half typed member is not worth storing).

## R4. An email is unique within the company's team

- Decision: `CreateStaff` and `UpdateStaffInformation` refuse an email already used by another staff member of the same company, compared lower case. The controller turns the refusal into an error under the email field.
- This applies in Settings as well. No production data exists, so no existing duplicate needs cleaning.

## R5. The role picker

- Decision: a native `<select>` styled as `x-input`, listing the company's roles by id (Owner, Manager, Staff, Receptionist first, then custom ones). Role names are shown as stored, as the roles screen in Settings does, rather than through `__()`. Staff is selected by default. It is shown with `x-show` while the invitation box is ticked and an email is typed.
- `PrepareStaffInvitation` checks the role belongs to the company and throws `ModelNotFoundException` otherwise (spec edge case).

## R6. The email preview is drawn on the client

- The preview must reflect the first name, the email and the job title as they are typed, before saving.
- Decision: the panel's Alpine state holds the field values; the dialog fills translated sentences carrying `:first_name`, `:inviter`, `:salon` and `:job_title` placeholders by string replacement. The sentences are written once in the view model through `__()`, so they are translated like any other copy. Sender is the salon name and `config('mail.from.address')`.
- The job title is shown as typed, not lower cased as in the design: lower casing a job title is wrong in German and alters names such as "CEO".
- The dialog reuses the markup of `x-confirm-dialog` for its focus trap, Escape and backdrop behaviour, and returns focus to "Preview the email".

## R7. The panel opens through its own address

- Decision: as in step 2, `GET onboarding/staff/members/new` and `GET onboarding/staff/members/{staff}/edit` render the step with the panel open (`openStaff` on the view model), and Alpine Ajax swaps only `staff-panel`. Closing loads `onboarding.staff.index` into `staff-panel`. A reload keeps the panel open.

- The open panel sits in `staff-panel-{id|new}` and the closed state in `staff-panel-closed`, as step 2 does, because Ajax responses are merged by morphing and two states sharing no id would be patched into each other.
- "Name shown to clients" falls back to the full name, since `Staff::name` already does, not to the first name as the design's help text says.

## R8. Removal is confirmed in the panel

- Decision: "Remove from the team" reveals, in the panel, the sentence and "Remove them" and "Keep them", as drawn; "Remove them" submits `DestroyStaff`. This is the warning principle V asks for, so no undo is offered, unlike step 2.
- `DestroyStaff` does not protect the author's own record. The controller answers 404 for it, and the panel never offers removal there.

## R9. Footer and header

- The footer is the step 2 footer: in the page flow, under a top rule, not sticky (spec FR-014). It holds the status sentence and the two actions, which both submit `PUT onboarding/staff` and complete the step, exactly as step 2's do.
- The header keeps the shared `_header` partial (breadcrumb, eyebrow "Onboarding · Step 3 of 4", "Finish later"), as step 2 does, rather than the design's "Back to your services" link and eyebrow, so the four steps stay identical.

## R10. API and webhooks

- No API and no webhook exist yet (constitution VII); the pull request says so, as specs 008 and 009 did.
