<div>

  <h1>Welcome to OpenBroadcaster</h1>

  <p id="login_welcome"><?=nl2br(htmlspecialchars($welcome_message))?></p>
  
  <?php /* form/submit tags used so browser offers to save password. does not function without javascript. */ ?>
  <form method="post" action="index.php" onSubmit="return false;">
    <p id="login_message"></p>
    <input aria-label="Username" name="ob_login_username" id="login_username" type="text" placeholder="Username">
    <input aria-label="Password" name="ob_login_password" id="login_password" type="password" placeholder="Password">
    <input id="login_submit" aria-label="Log In" type="submit" value="Log In" onclick="OB.Welcome.login();">
  </form>

  <div class="welcome_actions">
    <a href="javascript: OB.Welcome.show('forgotpass');">Forgot Password?</a>
    <?php
    $load = OBFLoad::get_instance();
    $user_model = $load->model('users');
    if ($user_model->user_registration_get()) { ?>
      <a href="javascript: OB.Welcome.show('newaccount');">Create New Account</a>
    <?php } ?>
  </div>

</div>
