<?php
require_once __DIR__ . '/includes/functions.php';
expire_donations($pdo);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Food listing not found.'); }
$stmt = $pdo->prepare('SELECT d.*, u.name donor_name FROM food_donations d JOIN users u ON u.id=d.donor_id WHERE d.id=?');
$stmt->execute([$id]);
$food = $stmt->fetch();
if (!$food) { http_response_code(404); exit('Food listing not found.'); }
$errors = [];
$eligible = current_user() && in_array(current_user()['role'], ['recipient', 'charity'], true) && current_user()['id'] != $food['donor_id'];
$canSeePickup = current_user() && (current_user()['id'] == $food['donor_id'] || current_user()['role'] === 'admin');
if (current_user() && in_array(current_user()['role'], ['recipient', 'charity'], true)) {
    $access = $pdo->prepare("SELECT 1 FROM collection_requests WHERE donation_id=? AND recipient_id=? AND status IN ('Accepted','Collected') LIMIT 1");
    $access->execute([$id, current_user()['id']]);
    $canSeePickup = $canSeePickup || (bool)$access->fetchColumn();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['recipient', 'charity']);
    verify_csrf();
    $quantity = filter_var($_POST['requested_quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    $message = trim($_POST['request_message'] ?? '');
    if (current_user()['id'] == $food['donor_id']) $errors[] = 'You cannot request your own listing.';
    if ($food['status'] === 'Expired' || strtotime($food['expiry_at']) <= time()) $errors[] = 'This food listing has expired.';
    if (!in_array($food['status'], ['Active', 'Partially Collected'], true) || (float)$food['remaining_quantity'] <= 0) $errors[] = 'This food is no longer available.';
    if ($quantity === false || $quantity <= 0 || $quantity > (float)$food['remaining_quantity']) $errors[] = 'Requested quantity must be positive and no more than the remaining quantity.';
    if (mb_strlen($message) > 500) $errors[] = 'The request message is too long.';
    if (!$errors) {
        try {
            $insert = $pdo->prepare('INSERT INTO collection_requests (donation_id, recipient_id, requested_quantity, request_message) VALUES (?, ?, ?, ?)');
            $insert->execute([$id, current_user()['id'], $quantity, $message]);
            flash('success', 'Collection request sent. The donor will review it.');
            redirect('my-requests.php');
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000' ? 'You already have a request for this listing.' : 'Could not submit your request. Please try again.';
        }
    }
}
$page_title = $food['food_name'] . ' | SharePlate';
require __DIR__ . '/includes/header.php';
?>
<section class="container section">
  <a class="back-link" href="available-food.php">← Back to available food</a>
  <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
  <div class="detail-layout">
    <div class="detail-image"><img src="<?= e(image_url($food['image'])) ?>" alt="<?= e($food['food_name']) ?>"></div>
    <article class="detail-content">
      <span class="badge badge-category"><?= e($food['category']) ?></span>
      <h1><?= e($food['food_name']) ?></h1>
      <p class="detail-description"><?= nl2br(e($food['description'])) ?></p>
      <div class="detail-stats">
        <div><strong><?= e(rtrim(rtrim(number_format((float)$food['remaining_quantity'], 2, '.', ''), '0'), '.')) ?> <?= e($food['unit']) ?></strong><span>available now</span></div>
        <div><strong data-countdown="<?= e(date(DATE_ATOM, strtotime($food['expiry_at']))) ?>"></strong><span>until pickup deadline</span></div>
      </div>
      <dl class="detail-list">
        <div><dt>Prepared</dt><dd><?= e(format_datetime($food['prepared_at'])) ?></dd></div>
        <div><dt>Pickup by</dt><dd><?= e(format_datetime($food['expiry_at'])) ?></dd></div>
        <div><dt>Pickup location</dt><dd><?= $canSeePickup ? e($food['pickup_address']) : 'Exact address shared after your request is accepted.' ?>, <?= e($food['locality']) ?></dd></div>
        <?php if ($canSeePickup): ?><div><dt>Donor contact</dt><dd><?= e($food['contact_number']) ?></dd></div><?php endif; ?>
        <div><dt>Ingredients</dt><dd><?= e($food['ingredients'] ?: 'Not provided') ?></dd></div>
        <div><dt>Allergens</dt><dd><?= e($food['allergens'] ?: 'Not provided') ?></dd></div>
        <div><dt>Pickup instructions</dt><dd><?= nl2br(e($food['pickup_instructions'] ?: 'Coordinate pickup after your request is accepted.')) ?></dd></div>
        <div><dt>Shared by</dt><dd><?= e($food['donor_name']) ?> · <?= e($food['locality']) ?></dd></div>
      </dl>
      <?php if (in_array($food['status'], ['Active', 'Partially Collected'], true) && strtotime($food['expiry_at']) > time() && (float)$food['remaining_quantity'] > 0): ?>
        <div class="request-box">
          <h2>Request a collection</h2>
          <?php if (!current_user()): ?>
            <p><a href="login.php">Log in</a> as a recipient to request food.</p>
          <?php elseif ($eligible): ?>
            <form method="post" class="stack-form" data-validate>
              <?= csrf_field() ?>
              <label>Quantity (max <?= e($food['remaining_quantity']) ?> <?= e($food['unit']) ?>)<input name="requested_quantity" type="number" min="0.01" max="<?= e($food['remaining_quantity']) ?>" step="0.01" value="<?= e($_POST['requested_quantity'] ?? $food['remaining_quantity']) ?>" required></label>
              <label>Message to donor<textarea name="request_message" rows="2" maxlength="500" placeholder="Share any pickup details"><?= e($_POST['request_message'] ?? '') ?></textarea></label>
              <button class="button">Request collection</button>
            </form>
          <?php elseif (current_user()['id'] == $food['donor_id']): ?>
            <p>This is your listing. Manage requests from My Donations.</p>
          <?php else: ?>
            <p>Your account is not set up as a recipient or charity.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">This listing is no longer accepting collection requests (<?= e(donation_status_label($food)) ?>).</div>
      <?php endif; ?>
      <?php if (current_user() && in_array(current_user()['role'], ['recipient', 'charity'], true) && current_user()['id'] != $food['donor_id']): ?><p class="report-link"><a href="report.php?id=<?= (int)$id ?>">Report this listing</a></p><?php endif; ?>
    </article>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>