<?php
$current_page = basename($_SERVER['PHP_SELF']);
$register_class = ($current_page == 'login.php') ? 'btn-outline-blue' : 'btn-register';
$login_class = ($current_page == 'register.php') ? 'btn-outline-orange' : 'btn-login';
?>

<style>
  header {
    display: flex;
    width: 100%;
    height: 10vh;
    align-items: center;
    justify-content: space-between;
    padding: 0 3vw;
    background: #F5F1ED;
    position: relative;
    z-index: 1000;
  }

  .logo-container {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
  }

  .logo-icon img {
    width: auto;
    height: 3vh;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .logo-text {
    font-size: 3vh;
    font-weight: 700;
    color: #FF6B35;
    letter-spacing: 0.5px;
  }

  .nav-buttons {
    display: flex;
    gap: 1vw;
    position: relative;
    z-index: 9999;
    pointer-events: auto;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    width: 8vw;
    height: 4.5vh;
    border-radius: 25px;
    font-weight: 600;
    font-size: 2vh;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'SF Pro', -apple-system, BlinkMacSystemFont, sans-serif;
    border: 2px solid transparent;
  }

  .btn-register {
    background: var(--btn-blue);
    color: white;
  }
  .btn-register:hover {
    transform: translateY(-2px);
  }

  .btn-login {
    background: var(--btn-orange);
    color: white;
  }
  .btn-login:hover {
    background: #E55A2B;
    transform: translateY(-2px);
  }

  .btn-outline-blue {
    background: transparent;
    color: var(--btn-blue);
    border: 2px solid var(--btn-blue);
  }
  .btn-outline-blue:hover {
    background: var(--btn-blue);
    color: white;
    transform: translateY(-2px);
  }

  .btn-outline-orange {
    background: transparent;
    color: var(--btn-orange);
    border: 2px solid var(--btn-orange);
  }
  .btn-outline-orange:hover {
    background: var(--btn-orange);
    color: white;
    transform: translateY(-2px);
  }
</style>

<header>
    <a href="/SwapifyFinal2/index.php" class="logo-container">
        <div class="logo-icon">
            <img src="/SwapifyFinal2/images/v91_89.png" alt="logo">
        </div>
        <span class="logo-text">SWAPIFY</span>
    </a>
    <div class="nav-buttons">
        <a href="/SwapifyFinal2/register.php" class="btn <?php echo $register_class; ?>">Register</a>
        <a href="/SwapifyFinal2/login.php" class="btn <?php echo $login_class; ?>">Login</a>
    </div>
</header>