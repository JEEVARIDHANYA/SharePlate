<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('index.php');
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();
  $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $phone=trim($_POST['phone']??''); $role=$_POST['role']??''; $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
  if ($name==='' || mb_strlen($name)>120) $errors[]='Enter a name up to 120 characters.';
  if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Enter a valid email address.';
  if (!in_array($role,['donor','recipient','charity'],true)) $errors[]='Choose a valid account type.';
  if (strlen($password)<8) $errors[]='Password must contain at least 8 characters.';
  if ($password!==$confirm) $errors[]='Passwords do not match.';
  if (!$errors) {
    try { $q=$pdo->prepare('INSERT INTO users (name,email,password,phone,role) VALUES (?,?,?,?,?)'); $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$phone,$role]); flash('success','Account created. You can now sign in.'); redirect('login.php'); }
    catch (PDOException $e) { if ($e->getCode()==='23000') $errors[]='An account already uses that email.'; else $errors[]='Unable to create the account right now.'; }
  }
}
$page_title='Create an account | SharePlate'; require __DIR__.'/includes/header.php';
?>
<section class="form-section"><div class="form-card"><p class="eyebrow">Join your community</p><h1>Create your account</h1><p class="muted">Choose how you would like to take part in sharing food.</p>
<?php foreach($errors as $error): ?><div class="alert alert-error"><?=e($error)?></div><?php endforeach; ?>
<form method="post" class="stack-form" data-validate><?=csrf_field()?>
<label>Full name<input name="name" required maxlength="120" autocomplete="name" value="<?=e($_POST['name']??'')?>"></label>
<label>Email address<input name="email" type="email" required autocomplete="email" value="<?=e($_POST['email']??'')?>"></label>
<label>Phone number <span class="optional">(optional)</span><input name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?=e($_POST['phone']??'')?>"></label>
<label>Account type<select name="role" required><option value="">Select a role</option><?php foreach(['donor'=>'Food donor','recipient'=>'Food recipient','charity'=>'Charitable organization'] as $value=>$label): ?><option value="<?=e($value)?>" <?=($_POST['role']??'')===$value?'selected':''?>><?=e($label)?></option><?php endforeach; ?></select></label>
<label>Password<input name="password" type="password" minlength="8" required autocomplete="new-password"></label><label>Confirm password<input name="confirm_password" type="password" required autocomplete="new-password"></label>
<button class="button button-full">Create account</button></form><p class="form-foot">Already a member? <a href="login.php">Sign in</a></p></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>