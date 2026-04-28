<?php
session_start();
$servername = "localhost"; $username = "root"; $password = ""; $dbname = "kitchen_monitor_db";
$conn = new mysqli($servername, $username, $password, $dbname);

// Login Logic (Admin & Visitor tracking added here)
if (isset($_POST['login_user'])) {
    $name = $conn->real_escape_string($_POST['l_name']);
    $email = $conn->real_escape_string($_POST['l_email']);
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    // Set Role
    if (strtolower($name) == 'admin') {
        $_SESSION['role'] = 'admin';
    } else {
        $_SESSION['role'] = 'user';
    }

    // Save to Visitor Logs
    @$conn->query("INSERT INTO visitor_logs (username, email) VALUES ('$name', '$email')");
    header("Location: index.php#feedback"); 
    exit(); 
}

// Contact/Feedback Logic
$notif_msg = "";
if (isset($_POST['submit_contact'])) {
    $notif_msg = "MESSAGE SENT TO ADMIN STRATUM";
}

if (isset($_POST['submit_feedback'])) {
    $u_name = $_SESSION['user_name'] ?? 'Guest';
    $u_rating = $_POST['u_rating'];
    $u_comment = $conn->real_escape_string($_POST['u_comment']);
    $sql = "INSERT INTO user_feedback (user_name, rating, comment) VALUES ('$u_name', '$u_rating', '$u_comment')";
    if($conn->query($sql)) { $notif_msg = "FEEDBACK ENCRYPTED & SYNCED"; }
}

$foods = $conn->query("SELECT * FROM food_items");

// Fetch Admin Data
$visitor_data = @$conn->query("SELECT * FROM visitor_logs ORDER BY id DESC");
$feedback_data = @$conn->query("SELECT * FROM user_feedback ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KitchGuard OS | Full-System Integration</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent: #00d4ff; --border: rgba(255,255,255,0.1); --bg: #06080a; --glass: rgba(255,255,255,0.03); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        body { background: var(--bg); color: #fff; overflow-x: hidden; }

        /* --- NAVIGATION BAR --- */
        nav { 
            position: fixed; width: 100%; padding: 25px 8%; display: flex; 
            justify-content: space-between; align-items: center; z-index: 1000; 
            background: linear-gradient(to bottom, rgba(6,8,10,0.8), transparent);
            backdrop-filter: blur(5px);
        }
        .logo { font-size: 20px; font-weight: 800; color: #fff; text-decoration: none; letter-spacing: -1px; }
        .logo span { color: var(--accent); }
        .nav-links a { margin-left: 30px; text-decoration: none; color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .nav-links a:hover { color: var(--accent); }

        /* --- IMMERSIVE HERO & SLIDER --- */
        #hero { position: relative; height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .slider-wrapper { position: absolute; width: 100%; height: 100%; z-index: 1; background: #000; }
        .slide { 
            position: absolute; width: 100%; height: 100%; 
            background-size: cover; background-position: center; 
            opacity: 0; transition: opacity 1.5s ease-in-out; 
        }
        .slide.active { opacity: 0.5; }

        /* --- TRANSPARENT LOGIN FORM --- */
        .login-card { 
            position: relative; z-index: 10; width: 100%; max-width: 400px;
            padding: 50px; border-radius: 24px; border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(20px);
            text-align: center;
        }

        /* --- SHARED FORM STYLES --- */
        input, select, textarea { 
            width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); 
            padding: 15px; color: white; border-radius: 10px; margin-bottom: 20px; outline: none; 
        }
        input:focus { border-color: var(--accent); background: rgba(255,255,255,0.05); }
        .btn { 
            width: 100%; padding: 15px; background: var(--accent); color: #000; 
            border: none; border-radius: 10px; font-weight: 800; cursor: pointer; 
            text-transform: uppercase; transition: 0.3s;
        }
        .btn:hover { background: #fff; transform: translateY(-2px); }

        /* --- ABOUT US SECTION --- */
        #about { padding: 120px 8%; background: #090c10; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
        .glass-box { background: var(--glass); border: 1px solid var(--border); padding: 40px; border-radius: 20px; transition: 0.3s; }
        .glass-box:hover { border-color: var(--accent); background: rgba(255,255,255,0.06); }

        /* --- CONTACT FORM SECTION --- */
        #contact { padding: 100px 8%; background: #06080a; }
        .contact-container { display: flex; gap: 50px; align-items: flex-start; }
        .contact-info { flex: 1; }
        .contact-form-wrapper { flex: 1.5; background: #0d1117; padding: 50px; border-radius: 24px; border: 1px solid var(--border); }

        /* --- ADMIN DASHBOARD SECTION --- */
        #admin-panel { padding: 100px 8%; background: #000; border-top: 2px solid var(--accent); }
        .admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px; }
        .admin-card { background: #0d1117; padding: 30px; border-radius: 20px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th { text-align: left; color: var(--accent); padding-bottom: 15px; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); color: #8b949e; }

        /* --- NOTIFICATION --- */
        #toast { position: fixed; bottom: 30px; right: 30px; background: var(--accent); color: #000; padding: 15px 30px; border-radius: 10px; font-weight: 800; display: none; z-index: 5000; }

        .label { color: var(--accent); font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 10px; display: block; }
    </style>
</head>
<body onload="<?php if($notif_msg != '') echo 'showToast()'; ?>">

    <div id="toast"><?php echo $notif_msg; ?></div>

    <nav>
        <a href="#" class="logo">KITCH<span>GUARD</span></a>
        <div class="nav-links">
            <a href="#hero">Home</a>
            <a href="#about">About</a>
            <a href="#feedback">Feedback</a>
            <a href="#contact">Contact</a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <a href="dashboard.php" style="color: #FFD700; border: 1px solid #FFD700; padding: 5px 10px; border-radius: 4px;">LIVE SENSORS</a>
            <?php endif; ?>
        </div>
    </nav>

    <section id="hero">
        <div class="slider-wrapper">
            <div class="slide active" style="background-image: url('https://images.unsplash.com/photo-1556910103-1c02745aae4d?q=80&w=1600');"></div>
            <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1590846406792-0adc7f938f1d?q=80&w=1600');"></div>
            <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1514328537441-c33fc52b610f?q=80&w=1600');"></div>
        </div>

        <div class="login-card">
            <span class="label">System Access</span>
            <h2 style="font-size: 26px; margin-bottom: 10px;">User Login</h2>
            <p style="color: #8b949e; font-size: 12px; margin-bottom: 30px;">Initialize your secure session</p>
            <form method="POST">
                <input type="text" name="l_name" placeholder="Full Name" required>
                <input type="email" name="l_email" placeholder="Email Address" required>
                <button type="submit" name="login_user" class="btn">Authenticate</button>
            </form>
        </div>
    </section>

    <section id="about">
        <span class="label">The Protocol</span>
        <h2 style="font-size: 36px; margin-bottom: 50px;">Autonomous Kitchen Safety</h2>
        <div class="grid-3">
            <div class="glass-box">
                <h3 style="color:var(--accent); margin-bottom:15px;">AI Monitoring</h3>
                <p style="color:#8b949e">Real-time visual and thermal analysis of kitchen operations to ensure 100% hygiene compliance.</p>
            </div>
            <div class="glass-box">
                <h3 style="color:var(--accent); margin-bottom:15px;">Instant Feedback</h3>
                <p style="color:#8b949e">Direct bridge between consumers and kitchen management for immediate quality calibration.</p>
            </div>
            <div class="glass-box">
                <h3 style="color:var(--accent); margin-bottom:15px;">Smart Logistics</h3>
                <p style="color:#8b949e">Predictive ingredient tracking that eliminates food waste and ensures freshness.</p>
            </div>
        </div>
    </section>

    <section id="feedback" style="background: #010409; padding: 100px 8%;">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <span class="label">Calibration</span>
            <h2 style="margin-bottom: 40px;">Quality Feedback Loop</h2>
            <p style="margin-bottom: 30px;">Active Session: <b style="color:var(--accent);"><?php echo $_SESSION['user_name'] ?? 'Guest_User'; ?></b></p>
            
            <form method="POST" style="background: #0d1117; padding: 40px; border-radius: 20px; border: 1px solid var(--border); text-align: left;">
                <label class="label">Rating Scale</label>
                <select name="u_rating">
                    <option value="5">⭐⭐⭐⭐⭐ Optimal</option>
                    <option value="4">⭐⭐⭐⭐ Satisfactory</option>
                    <option value="2">⭐⭐ Critical Maintenance Req.</option>
                </select>
                <label class="label">Detailed Observation</label>
                <textarea name="u_comment" placeholder="Log hygiene or taste notes..." rows="4"></textarea>
                <button type="submit" name="submit_feedback" class="btn">Transmit to Terminal</button>
            </form>
        </div>
    </section>

    <section id="contact">
        <div class="contact-container">
            <div class="contact-info">
                <span class="label">Connect</span>
                <h2 style="font-size: 40px; line-height: 1.1;">Reach Out to Our <br>Tech Team.</h2>
                <div style="margin-top: 40px;">
                    <p style="color:var(--accent); font-weight: 800;">Mobile</p>
                    <p style="font-size: 20px;">+91 80 2233 4455</p>
                </div>
                <div style="margin-top: 20px;">
                    <p style="color:var(--accent); font-weight: 800;">Email</p>
                    <p style="font-size: 20px;">admin@kitchguard.io</p>
                </div>
            </div>

            <div class="contact-form-wrapper">
                <span class="label">Secure Message</span>
                <form method="POST">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="text" placeholder="First Name" required>
                        <input type="text" placeholder="Last Name" required>
                    </div>
                    <input type="email" placeholder="Your Email" required>
                    <textarea placeholder="Your Message..." rows="5"></textarea>
                    <button type="submit" name="submit_contact" class="btn">Send Encrypted Message</button>
                </form>
            </div>
        </div>
    </section>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
    <section id="admin-panel">
        <span class="label">Master Terminal</span>
        <h2 style="font-size: 32px;">System Analytics Dashboard</h2>
        <div class="admin-grid">
            <div class="admin-card">
                <h4 style="color:var(--accent)">User Login History</h4>
                <table>
                    <tr><th>Username</th><th>Email Address</th></tr>
                    <?php while($v = $visitor_data->fetch_assoc()): ?>
                    <tr><td><?php echo $v['username']; ?></td><td><?php echo $v['email']; ?></td></tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <div class="admin-card">
                <h4 style="color:var(--accent)">User Feedback Logs</h4>
                <table>
                    <tr><th>User</th><th>Rating</th><th>Notes</th></tr>
                    <?php while($f = $feedback_data->fetch_assoc()): ?>
                    <tr><td><?php echo $f['user_name']; ?></td><td><?php echo $f['rating']; ?>/5</td><td><?php echo substr($f['comment'], 0, 20); ?>...</td></tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer style="padding: 50px 8%; border-top: 1px solid var(--border); text-align: center; font-size: 11px; color: #444;">
        &copy; 2026 KITCHGUARD PRO | NEURAL KITCHEN OPERATING SYSTEM
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="dashboard.php" style="color: var(--accent); text-decoration: none; font-weight: 800; display: block; margin-top: 15px;">ADMIN LOGIN</a>
        <?php endif; ?>
    </footer>

    <script>
        // SLIDER ENGINE
        let current = 0;
        const slides = document.querySelectorAll('.slide');
        function nextSlide() {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }
        setInterval(nextSlide, 5000);

        // TOAST NOTIFICATION
        function showToast() {
            const toast = document.getElementById('toast');
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 4000);
        }
		
    </script>
	
    
<center>
    <a href="index.html" style="display: inline-block; margin-top: 15px; color: #00d4ff; text-decoration: none; border: 1px solid #00d4ff; padding: 10px 20px; border-radius: 5px;">
        OPEN ADMIN DASHBOARD
    </a></center>

</footer>
</body>
</html>