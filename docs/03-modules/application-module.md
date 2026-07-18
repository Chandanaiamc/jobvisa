# Application Module

End-to-end job application lifecycle from seeker submission to employer decision.

**Phase 1 (API):** See [`docs/05-api/job-applications-phase1.md`](../05-api/job-applications-phase1.md).

- Apply / My Applications / Withdraw (seeker)
- Applicant list & detail / Status updates (employer)
- Status history table `application_status_history`
- Live resume attachment (no snapshot yet)
- Duplicate prevention via unique `(job_id, user_id)` + 409 / reopen-after-withdraw

**Not in Phase 1:** notifications, interviews, messaging, payments, session MVC apply forms, CV snapshots.
