<?php

use App\Utils\Csrf;

$csrf = Csrf::token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facebook Event Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-3">Facebook Page Event Automation</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header">1) Facebook Authentication</div>
        <div class="card-body">
            <a href="/facebook-login" class="btn btn-primary mb-3">Connect Facebook</a>
            <form method="post" action="/select-page" class="row g-2 align-items-end">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-md-8">
                    <label class="form-label">Select Page</label>
                    <select name="page_id" class="form-select" required>
                        <option value="">Choose a page...</option>
                        <?php foreach ($pages as $page): ?>
                            <option value="<?= htmlspecialchars($page['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                <?= (($selectedPage['id'] ?? '') === ($page['id'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($page['name'] ?? 'Unknown Page', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100">Save Page</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">2) Upload Excel File</div>
        <div class="card-body">
            <form method="post" action="/upload" enctype="multipart/form-data" class="row g-2 align-items-end">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-md-9">
                    <label class="form-label">.xlsx file</label>
                    <input type="file" name="events_file" class="form-control" accept=".xlsx" required>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-success w-100">Upload & Validate</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>3) Event Preview</span>
            <span class="badge text-bg-secondary">Rows: <?= count($events) ?></span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered" id="eventsTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Type</th>
                    <th>Venue / Online</th>
                    <th>Status</th>
                    <th>Error</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $i => $event): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($event['start_date'] . ' ' . $event['start_time'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($event['end_date'] . ' ' . $event['end_time'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($event['event_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($event['venue_name'] ?: 'Virtual', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge text-bg-<?= $event['status'] === 'Posted' ? 'success' : ($event['status'] === 'Failed' ? 'danger' : 'warning') ?>">
                                <?= htmlspecialchars($event['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($event['error_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">4) Bulk Submission</div>
        <div class="card-body">
            <form method="post" action="/submit-events">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-danger" <?= empty($events) ? 'disabled' : '' ?>>Submit Events to Facebook</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
