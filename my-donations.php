<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['donor', 'admin']);
expire_donations($pdo);
$uid = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    $donationId = filter_var($_POST['donation_id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'cancel' && $donationId) {
        $cancel = $pdo->prepare("UPDATE food_donations SET status='Cancelled' WHERE id=? AND donor_id=? AND status IN ('Active','Partially Collected') AND expiry_at>NOW() AND NOT EXISTS (SELECT 1 FROM collection_requests WHERE donation_id=? AND status='Accepted')");
        $cancel->execute([$donationId, $uid, $donationId]);
        flash($cancel->rowCount() ? 'success' : 'error', $cancel->rowCount() ? 'Listing cancelled.' : 'This listing can no longer be cancelled.');
        redirect('my-donations.php');
    }

    if ($action === 'review' && $requestId && in_array($_POST['decision'] ?? '', ['Accepted', 'Rejected'], true)) {
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT r.*, d.donor_id, d.remaining_quantity, d.status donation_status, d.expiry_at, d.id donation_key FROM collection_requests r JOIN food_donations d ON d.id=r.donation_id WHERE r.id=? FOR UPDATE');
            $lock->execute([$requestId]);
            $request = $lock->fetch();
            if (!$request || $request['donor_id'] != $uid || $request['status'] !== 'Pending' || !in_array($request['donation_status'], ['Active', 'Partially Collected'], true) || strtotime($request['expiry_at']) <= time()) {
                throw new RuntimeException('Request is no longer eligible for review.');
            }
            if ($_POST['decision'] === 'Accepted') {
                $sum = $pdo->prepare("SELECT COALESCE(SUM(requested_quantity),0) FROM collection_requests WHERE donation_id=? AND status='Accepted'");
                $sum->execute([$request['donation_key']]);
                $reserved = (float)$sum->fetchColumn();
                if ((float)$request['requested_quantity'] > (float)$request['remaining_quantity'] - $reserved) {
                    throw new RuntimeException('Not enough unreserved food remains to accept this request.');
                }
            }
            $update = $pdo->prepare('UPDATE collection_requests SET status=? WHERE id=?');
            $update->execute([$_POST['decision'], $requestId]);
            $pdo->commit();
            flash('success', 'Request ' . strtolower($_POST['decision']) . '.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update request.');
        }
        redirect('my-donations.php');
    }

    if ($action === 'collected' && $requestId) {
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT r.*, d.donor_id, d.remaining_quantity, d.status donation_status, d.expiry_at FROM collection_requests r JOIN food_donations d ON d.id=r.donation_id WHERE r.id=? FOR UPDATE');
            $lock->execute([$requestId]);
            $request = $lock->fetch();
            if (!$request || $request['donor_id'] != $uid || $request['status'] !== 'Accepted' || !in_array($request['donation_status'], ['Active', 'Partially Collected'], true) || strtotime($request['expiry_at']) <= time()) {
                throw new RuntimeException('This accepted request can no longer be completed.');
            }
            if ((float)$request['requested_quantity'] > (float)$request['remaining_quantity']) throw new RuntimeException('Not enough remaining quantity.');
            $pdo->prepare("UPDATE collection_requests SET status='Collected' WHERE id=?")->execute([$requestId]);
            $pdo->prepare('INSERT INTO collection_records (donation_id, request_id, donor_id, recipient_id, collected_quantity) VALUES (?, ?, ?, ?, ?)')->execute([$request['donation_id'], $requestId, $uid, $request['recipient_id'], $request['requested_quantity']]);
            $remaining = max(0, (float)$request['remaining_quantity'] - (float)$request['requested_quantity']);
            $status = $remaining <= 0 ? 'Fully Collected' : 'Partially Collected';
            $pdo->prepare('UPDATE food_donations SET remaining_quantity=?, status=? WHERE id=?')->execute([$remaining, $status, $request['donation_id']]);
            $pdo->commit();
            flash('success', 'Collection completed and quantity updated.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'Unable to complete collection.');
        }
        redirect('my-donations.php');
    }
}

$list = $pdo->prepare('SELECT d.* FROM food_donations d WHERE d.donor_id=? ORDER BY d.created_at DESC');
$list->execute([$uid]);
$donations = $list->fetchAll();
$requestList = $pdo->prepare('SELECT r.*, u.name recipient_name, u.locality recipient_locality, d.food_name, d.unit, d.expiry_at, d.status donation_status FROM collection_requests r JOIN food_donations d ON d.id=r.donation_id JOIN users u ON u.id=r.recipient_id WHERE d.donor_id=? ORDER BY r.requested_at DESC');
$requestList->execute([$uid]);
$requests = $requestList->fetchAll();
$page_title = 'My donations | SharePlate';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact"><div class="container"><p class="eyebrow">Your community impact</p><h1>My donations</h1><p>Keep your listings up to date and coordinate safe, timely pickups.</p><a class="button" href="add-food.php">＋ Add a food listing</a></div></section>
<section class="container section">
  <h2>Your listings</h2>
  <?php if ($donations): ?>
    <div class="table-wrap"><table><thead><tr><th>Food</th><th>Quantity left</th><th>Added</th><th>Pickup deadline</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($donations as $donation): $status = donation_status_label($donation); ?>
      <tr><td><a href="food-details.php?id=<?= (int)$donation['id'] ?>"><?= e($donation['food_name']) ?></a></td><td><?= e($donation['remaining_quantity']) ?> <?= e($donation['unit']) ?></td><td><?= e(format_datetime($donation['created_at'])) ?></td><td><?= e(format_datetime($donation['expiry_at'])) ?></td><td><span class="badge badge-<?= e(strtolower(str_replace(' ', '-', $status))) ?>"><?= e($status) ?></span></td><td class="actions"><a href="food-details.php?id=<?= (int)$donation['id'] ?>">View</a><?php if (in_array($status, ['Active', 'Partially Collected'], true)): ?><a href="edit-food.php?id=<?= (int)$donation['id'] ?>">Edit</a><form method="post" class="inline-form" data-confirm="Cancel this listing?"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="donation_id" value="<?= (int)$donation['id'] ?>"><button class="link-button danger-text">Cancel</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?>
    <div class="empty-state"><h2>No donations yet</h2><p>Share surplus food and help someone nearby.</p><a class="button" href="add-food.php">Add your first listing</a></div>
  <?php endif; ?>
  <h2 class="section-subheading">Collection requests</h2>
  <?php if ($requests): ?><div class="request-list">
    <?php foreach ($requests as $request): ?>
      <article class="request-card"><div class="request-main"><span class="badge badge-<?= e(strtolower($request['status'])) ?>"><?= e($request['status']) ?></span><h3><?= e($request['food_name']) ?> · <?= e($request['requested_quantity']) ?> <?= e($request['unit']) ?></h3><p>Requested by <?= e($request['recipient_name']) ?><?= $request['recipient_locality'] ? ' in ' . e($request['recipient_locality']) : '' ?> on <?= e(format_datetime($request['requested_at'])) ?></p><?php if ($request['request_message']): ?><p class="muted">“<?= e($request['request_message']) ?>”</p><?php endif; ?></div>
        <div class="request-actions">
          <?php if ($request['status'] === 'Pending' && in_array($request['donation_status'], ['Active', 'Partially Collected'], true) && strtotime($request['expiry_at']) > time()): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="review"><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><input type="hidden" name="decision" value="Accepted"><button class="button button-small">Accept</button></form>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="review"><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><input type="hidden" name="decision" value="Rejected"><button class="button button-small button-outline">Reject</button></form>
          <?php elseif ($request['status'] === 'Accepted' && in_array($request['donation_status'], ['Active', 'Partially Collected'], true) && strtotime($request['expiry_at']) > time()): ?>
            <form method="post" data-confirm="Confirm this food has been collected?"><?= csrf_field() ?><input type="hidden" name="action" value="collected"><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><button class="button button-small">Mark collected</button></form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div><?php else: ?><p class="muted">No collection requests yet.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>