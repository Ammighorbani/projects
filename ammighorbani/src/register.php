<?php
session_start();
include 'db.php'; // <-- your mysqli $conn

function generate_uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}


$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $age          = intval($_POST['age'] ?? 0);
    $phone_prefix = trim($_POST['phone_prefix'] ?? ''); // like +98
    $phone_rest   = preg_replace('/\D/','', $_POST['phone_number'] ?? '');
    $phone_number = preg_replace('/\D/','', $phone_prefix . $phone_rest);
    # $country      = trim($_POST['country'] ?? '');

    // basic validation
    if ($first_name === '' || $last_name === '' || $email === '' || $username === '' ||
        $password_raw === '' || $age <= 0 || $phone_rest === '') {
        $message = "⚠️ Please fill all required fields.";
    } else {
        // duplicate check
        $chk = $conn->prepare("SELECT id FROM users_account WHERE username=? OR email=? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        $res = $chk->get_result();
        if ($res && $res->num_rows > 0) {
            $message = "⚠️ Username or Email already exists!";
            $chk->close();
        } else {
            $chk->close();
            $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);


            $uuid = generate_uuid();

            $sql = "INSERT INTO users_account
                (uuid,first_name,last_name,email,username,password,age,phone_number,country)
                VALUES (?,?,?,?,?,?,?,?,?)";
            if ($stmt = $conn->prepare($sql)) {
                // types: s s s s s i s s s s => "sssssisss"
                $stmt->bind_param(
                    "ssssssiss",
                    $uuid, $first_name, $last_name, $email, $username, $password_hashed,
                    $age, $phone_number, $country
                );
                if ($stmt->execute()) {
                    $_SESSION['success'] = "✅ Registered successfully.";
                    header("Location: login.php");
                    exit;
                } else {
                    $message = "❌ Registration failed: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                $message = "❌ Server error preparing statement.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Register - JobToGo</title>
  <style>
        /* register.css */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }

    body {
      min-height: 100vh;
      background: #0f0f0f;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .container {
      position: relative;
      width: 100%;
      max-width: 950px;
      padding: 20px;
    }

    .circle-1, .circle-2 {
      position: absolute;
      border-radius: 50%;
      z-index: 0;
    }

    .circle-1 {
      width: 250px;
      height: 250px;
      background: #3366ff;
      top: -60px;
      left: -60px;
      filter: blur(100px);
    }

    .circle-2 {
      width: 300px;
      height: 300px;
      background: #ff6600;
      bottom: -80px;
      right: -80px;
      filter: blur(120px);
    }

    .card {
      position: relative;
      z-index: 1;
      background: #1b1b1b;
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.5);
      color: #fff;
    }

    .card .title {
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 20px;
      text-align: center;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .row {
      display: grid;
      gap: 15px;
    }

    .row-1 {
      grid-template-columns: repeat(3, 1fr);
    }

    .row-2 {
      grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    /* .row-3 {
      grid-template-columns: repeat(3, 1fr);
    } */

    /* .row-4 {
      grid-template-columns: 2fr 1fr;
    } */

    label {
      display: block;
      font-size: 14px;
      margin-bottom: 5px;
      color: #ccc;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #333;
      background: #121212;
      color: #fff;
      font-size: 14px;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #3366ff;
      box-shadow: 0 0 8px rgba(51,102,255,0.5);
    }

    textarea {
      resize: vertical;
      min-height: 60px;
    }

    button.submit {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(90deg,#3366ff,#33ccff);
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
    }

    button.submit:hover {
      opacity: 0.9;
    }

    .phone-row {
      display: flex;
      gap: 8px;
    }

    .phone-prefix {
      width: 70px;
      text-align: center;
      background: #0d0d0d;
    }

    p {
      font-size: 14px;
    }

  </style>
</head>
<body>
  <div class="container">
    <div class="circle-1"></div>
    <div class="circle-2"></div>

    <div class="card">
      <div class="title">Register</div>
      <?php if ($message): ?>
        <p style="color:#FFCDD2;text-align:center;margin-bottom:10px;"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <form method="post" class="form-grid" id="regForm" autocomplete="off">
        <!-- Row 1 -->
        <div class="row row-1">
          <div>
            <label for="first_name">First Name</label>
            <input id="first_name" name="first_name" type="text" required>
          </div>
          <div>
            <label for="last_name">Last Name</label>
            <input id="last_name" name="last_name" type="text" required>
          </div>
          <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
          </div>
        </div>

        <!-- Row 2 -->
        <div class="row row-2">
          <div>
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required>
          </div>
          <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
          </div>
          <div>
            <label for="age">Age</label>
            <input id="age" name="age" type="number" min="1" max="120" required>
          </div>
          <div>
            <label for="phone_number">Phone Number</label>
            <div class="phone-row">
              <input id="phone_prefix" name="phone_prefix" class="phone-prefix" type="text" readonly value="+98">
              <input id="phone_number" name="phone_number" type="text" placeholder="9363896309" required>
            </div>
          </div>
        </div>

        <!-- Row 3 (country -> city -> province)
        <div class="row row-3">
          <div>
            <label for="country">Country</label>
            <select id="country" name="country" required>
              <option value="">Loading countries…</option>
            </select>
          </div>

          <div>
            <label for="city">City</label>
            <select id="city" name="city" required>
              <option value="">Select city</option>
            </select>
          </div>

          <div>
            <label for="province">Province</label>
            <select id="province" name="province" required>
              <option value="">Select province</option>
            </select>
          </div>
        </div> -->

        <!-- Row 4 
        <div class="row row-4">
          <div>
            <label for="home_address">Home Address</label>
            <textarea id="home_address" name="home_address"></textarea>
          </div>
          <div>
            <label for="postalcode">Postal Code</label>
            <input id="postalcode" name="postalcode" type="text">
          </div>
        </div> -->

        <button class="submit" type="submit">Register</button>
      </form>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const countrySel = document.getElementById('country');
  // const citySel = document.getElementById('city');
  // const provinceSel = document.getElementById('province');
  const phonePrefix = document.getElementById('phone_prefix');

  // Load local JSON once
  let countriesData = [];
  fetch('/data/countries+states+cities.json')
    .then(r => r.ok ? r.json() : Promise.reject('failed to load'))
    .then(json => {
      countriesData = json;

      // populate country select
      countrySel.innerHTML = '<option value="">Select Country</option>';
      countriesData.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.name;
        opt.textContent = c.name;
        opt.dataset.phone = c.phone_code || '';
        // store states in dataset for speed (stringify)
        opt.dataset.states = JSON.stringify(c.states || []);
        countrySel.appendChild(opt);
      });

      // attempt to set default to Iran if exists
      const iranOpt = Array.from(countrySel.options).find(o => o.value === 'Iran' || o.value === 'Islamic Republic of Iran');
      if (iranOpt) {
        iranOpt.selected = true;
        phonePrefix.value = iranOpt.dataset.phone ? `+${iranOpt.dataset.phone}` : phonePrefix.value;
        // populateCitiesFromCountryOption(iranOpt);
      } else {
        phonePrefix.value = countrySel.selectedOptions[0]?.dataset?.phone ? `+${countrySel.selectedOptions[0].dataset.phone}` : phonePrefix.value;
      }
    })
    .catch(err => {
      console.error('Load countries error', err);
      countrySel.innerHTML = '<option value="">Could not load</option>';
    });

  // When a country is selected:
  countrySel.addEventListener('change', (e) => {
    const opt = e.target.selectedOptions[0];
    phonePrefix.value = opt?.dataset?.phone ? `+${opt.dataset.phone}` : '';
    populateCitiesFromCountryOption(opt);
  });

  function populateCitiesFromCountryOption(opt) {
    citySel.innerHTML = '<option value="">Select city</option>';
    provinceSel.innerHTML = '<option value="">Select province</option>';
    if (!opt) return;
    let states;
    try { states = JSON.parse(opt.dataset.states || '[]'); } catch (ex) { states = []; }

    // collect all cities (flatten) from all states
    const citySet = new Set();
    states.forEach(s => {
      if (s && s.cities) {
        s.cities.forEach(c => {
          // city may be object {name:...} or string
          const cityName = (typeof c === 'object' && c.name) ? c.name : (typeof c === 'string' ? c : (c?.name || ''));
          if (cityName) citySet.add(cityName);
        });
      }
    });

    const cities = Array.from(citySet).sort((a,b)=>a.localeCompare(b));
    if (cities.length) {
      cities.forEach(cityName => {
        const o = document.createElement('option');
        o.value = cityName; o.textContent = cityName;
        citySel.appendChild(o);
      });
    } else {
      citySel.innerHTML = '<option value="">N/A</option>';
    }
  }

  // When a city is selected: find province(s) that contain that city
  // citySel.addEventListener('change', (e) => {
  //   provinceSel.innerHTML = '<option value="">Select province</option>';
  //   const countryName = countrySel.value;
  //   const cityName = e.target.value;
  //   if (!countryName || !cityName) return;

  //   const country = countriesData.find(c => c.name === countryName);
  //   if (!country || !Array.isArray(country.states)) return;

  //   // find states that include the city
  //   const matchedStates = [];
  //   country.states.forEach(s => {
  //     if (s && s.cities) {
  //       const found = s.cities.some(c => {
  //         const candidate = (typeof c === 'object' && c.name) ? c.name : (typeof c === 'string' ? c : c?.name || '');
  //         return candidate === cityName;
  //       });
  //       if (found) matchedStates.push(s.name || s.state || s.region || '');
  //     }
  //   });

    const uniqueStates = Array.from(new Set(matchedStates)).filter(x => x);
    if (uniqueStates.length) {
      uniqueStates.forEach(st => {
        const o = document.createElement('option');
        o.value = st; o.textContent = st;
        provinceSel.appendChild(o);
      });
    } else {
      provinceSel.innerHTML = '<option value="">N/A</option>';
    }
  });

});
</script>
</body>
</html>
