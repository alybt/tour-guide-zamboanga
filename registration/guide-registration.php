<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "../classes/registration.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$registrationObj = new Registration();
$guide = [];
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $guide = array_map(fn($v) => is_string($v) ? trim(htmlspecialchars($v)) : $v, $_POST);
    error_log("POST Data: " . print_r($guide, true));

    $required = [
        "name_first", "name_last",
        "address_houseno", "address_street", "barangay_ID", "country_ID",
        "emergency_name", "emergency_country_ID", "emergency_phonenumber", "emergency_relationship",
        "contactinfo_email", "person_nationality", "person_gender", "person_dateofbirth",
        "username", "password", "languages"
    ];

    foreach ($required as $field) {
        if (empty($guide[$field])) {
            $errors[$field] = ucfirst(str_replace("_", " ", $field)) . " is required.";
        }
    }

    if (!empty($guide["contactinfo_email"]) && !filter_var($guide["contactinfo_email"], FILTER_VALIDATE_EMAIL)) {
        $errors["contactinfo_email"] = "Invalid email format.";
    }

    if (!empty($guide["phone_number"]) && strlen($guide["phone_number"]) < 10) {
        $errors["phone_number"] = "Phone Number must be at least 10 digits.";
    }

    if (!empty($guide["emergency_phonenumber"]) && strlen($guide["emergency_phonenumber"]) < 10) {
        $errors["emergency_phonenumber"] = "Emergency Phone must be at least 10 digits.";
    }

    if (empty($guide["languages"]) || !is_array($guide["languages"])) {
        $errors["languages"] = "Please select at least one language.";
    }

    // Validate age - must be at least 18 years old
    if (!empty($guide["person_dateofbirth"])) {
        $today = new DateTime();
        $birthDate = new DateTime($guide["person_dateofbirth"]);
        $age = $today->diff($birthDate)->y;
        
        if ($age < 18) {
            $errors["person_dateofbirth"] = "You must be at least 18 years old to register.";
        }
    }

    if (empty($errors)) {
        try {
            $result = $registrationObj->addgetGuide(
                $guide["languages"],
                $guide["name_first"],
                $guide["name_second"] ?? "",
                $guide["name_middle"] ?? "",
                $guide["name_last"],
                $guide["name_suffix"] ?? "",
                $guide["address_houseno"],
                $guide["address_street"],
                $guide["barangay_ID"],
                $guide["country_ID"],
                $guide["phone_number"] ?? "",
                $guide["emergency_name"],
                $guide["emergency_country_ID"],
                $guide["emergency_phonenumber"],
                $guide["emergency_relationship"],
                $guide["contactinfo_email"],
                $guide["person_nationality"],
                $guide["person_gender"],
                $guide["person_dateofbirth"],
                $guide["username"],
                $guide["password"]
            );

            if ($result) {
                $license = $registrationObj->getAssignedLicense();
                $_SESSION['assigned_license'] = $license;
                $_SESSION['new_username'] = $guide['username'];

                // Send welcome email
                try {
                    require_once __DIR__ . '/../classes/mailer.php';
                    $mailer = new Mailer();
                    
                    $subject = "Welcome to Zamboanga Adventures - Guide Registration!";
                    $body = "
                        <h2>Hello {$guide['name_first']},</h2>
                        <p>Your guide account has been created successfully!</p>
                        <p><strong>Username:</strong> {$guide['username']}</p>
                        <p><strong>Guide License Number:</strong> {$license}</p>
                        <p>Your registration is pending admin approval. You will be notified once approved.</p>
                        <p>You can log in and view your profile at:</p>
                        <p><a href='https://yourdomain.com/login.php' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Click here to log in</a></p>
                        <p>Best regards,<br>Zamboanga Tourist Platform</p>
                    ";
                    
                    $emailResult = $mailer->send($guide['contactinfo_email'], $guide['name_first'], $subject, $body);
                    $_SESSION['email_sent'] = $emailResult['success'];
                    if (!$emailResult['success']) {
                        $_SESSION['email_error'] = $emailResult['message'] ?? 'Failed to send email';
                    }
                } catch (Exception $e) {
                    error_log("Guide registration email error: " . $e->getMessage());
                    $_SESSION['email_sent'] = false;
                    $_SESSION['email_error'] = 'Email notification could not be sent.';
                }

                header("Location: ?success=1");
                exit;
            } else {
                $errors["general"] = $registrationObj->getLastError() ?: "Failed to register guide.";
            }
        } catch (Exception $e) {
            $errors["general"] = "System error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guide Registration</title>
    <link rel="stylesheet" href="../assets/css/public-pages/guide-registration.css">
    
    <script>
        // Define functions in head to ensure they're available when HTML loads
        function toggleAddressFields(countryID) {
            console.log("Country ID selected:", countryID);
            
            // Get all elements first
            const regionDropdown = document.getElementById("region_dropdown_container");
            const regionText = document.getElementById("region_text_container");
            const provinceDropdown = document.getElementById("province_dropdown_container");
            const provinceText = document.getElementById("province_text_container");
            const cityDropdown = document.getElementById("city_dropdown_container");
            const cityText = document.getElementById("city_text_container");
            const barangayDropdown = document.getElementById("barangay_dropdown_container");
            const barangayText = document.getElementById("barangay_text_container");
            
            if (!countryID || countryID === "") {
                if (regionDropdown) regionDropdown.style.display = "none";
                if (regionText) regionText.style.display = "none";
                if (provinceDropdown) provinceDropdown.style.display = "none";
                if (provinceText) provinceText.style.display = "none";
                if (cityDropdown) cityDropdown.style.display = "none";
                if (cityText) cityText.style.display = "none";
                if (barangayDropdown) barangayDropdown.style.display = "none";
                if (barangayText) barangayText.style.display = "none";
                return;
            }
            
            const isPhilippines = (countryID == "161");
            console.log("Is Philippines?", isPhilippines);
            
            // Toggle Region
            if (regionDropdown) regionDropdown.style.display = isPhilippines ? "block" : "none";
            if (regionText) regionText.style.display = isPhilippines ? "none" : "block";
            const regionID = document.getElementById("region_ID");
            const regionName = document.getElementById("region_name");
            if (regionID) regionID.disabled = !isPhilippines;
            if (regionName) regionName.disabled = isPhilippines;
            
            // Toggle Province
            if (provinceDropdown) provinceDropdown.style.display = isPhilippines ? "block" : "none";
            if (provinceText) provinceText.style.display = isPhilippines ? "none" : "block";
            const provinceID = document.getElementById("province_ID");
            const provinceName = document.getElementById("province_name");
            if (provinceID) provinceID.disabled = !isPhilippines;
            if (provinceName) provinceName.disabled = isPhilippines;
            
            // Toggle City
            if (cityDropdown) cityDropdown.style.display = isPhilippines ? "block" : "none";
            if (cityText) cityText.style.display = isPhilippines ? "none" : "block";
            const cityID = document.getElementById("city_ID");
            const cityName = document.getElementById("city_name");
            if (cityID) cityID.disabled = !isPhilippines;
            if (cityName) cityName.disabled = isPhilippines;
            
            // Toggle Barangay
            if (barangayDropdown) barangayDropdown.style.display = isPhilippines ? "block" : "none";
            if (barangayText) barangayText.style.display = isPhilippines ? "none" : "block";
            const barangayID = document.getElementById("barangay_ID");
            const barangayName = document.getElementById("barangay_name");
            if (barangayID) barangayID.disabled = !isPhilippines;
            if (barangayName) barangayName.disabled = isPhilippines;
            
            // Clear values
            if (!isPhilippines) {
                if (regionID) regionID.value = "";
                if (provinceID) provinceID.value = "";
                if (cityID) cityID.value = "";
                if (barangayID) barangayID.value = "";
            } else {
                if (regionName) regionName.value = "";
                if (provinceName) provinceName.value = "";
                if (cityName) cityName.value = "";
                if (barangayName) barangayName.value = "";
                loadRegions(countryID);
            }
        }

        function loadRegions(countryID) {
            fetch("fetch-region.php?country_ID=" + countryID)
                .then(res => res.text())
                .then(data => {
                    const regionEl = document.getElementById("region_ID");
                    const provinceEl = document.getElementById("province_ID");
                    const cityEl = document.getElementById("city_ID");
                    const barangayEl = document.getElementById("barangay_ID");
                    
                    if (regionEl) regionEl.innerHTML = data;
                    if (provinceEl) provinceEl.innerHTML = "<option value=''>--SELECT PROVINCE--</option>";
                    if (cityEl) cityEl.innerHTML = "<option value=''>--SELECT CITY--</option>";
                    if (barangayEl) barangayEl.innerHTML = "<option value=''>--SELECT BARANGAY--</option>";
                })
                .catch(err => console.error("Error loading regions:", err));
        }

        function loadProvinces(regionID) {
            fetch("fetch-province.php?region_ID=" + regionID)
                .then(res => res.text())
                .then(data => {
                    const provinceEl = document.getElementById("province_ID");
                    const cityEl = document.getElementById("city_ID");
                    const barangayEl = document.getElementById("barangay_ID");
                    
                    if (provinceEl) {
                        provinceEl.innerHTML = data;
                        provinceEl.disabled = false; // Enable province dropdown
                    }
                    if (cityEl) {
                        cityEl.innerHTML = "<option value=''>--SELECT CITY--</option>";
                        cityEl.disabled = true; // Keep city disabled until province is selected
                    }
                    if (barangayEl) {
                        barangayEl.innerHTML = "<option value=''>--SELECT BARANGAY--</option>";
                        barangayEl.disabled = true; // Keep barangay disabled
                    }
                })
                .catch(err => console.error("Error loading provinces:", err));
        }

        function loadCities(provinceID) {
            fetch("fetch-city.php?province_ID=" + provinceID)
                .then(res => res.text())
                .then(data => {
                    const cityEl = document.getElementById("city_ID");
                    const barangayEl = document.getElementById("barangay_ID");
                    
                    if (cityEl) {
                        cityEl.innerHTML = data;
                        cityEl.disabled = false; // Enable city dropdown
                    }
                    if (barangayEl) {
                        barangayEl.innerHTML = "<option value=''>--SELECT BARANGAY--</option>";
                        barangayEl.disabled = true; // Keep barangay disabled until city is selected
                    }
                })
                .catch(err => console.error("Error loading cities:", err));
        }

        function loadBarangays(cityID) {
            fetch("fetch-barangay.php?city_ID=" + cityID)
                .then(res => res.text())
                .then(data => {
                    const barangayEl = document.getElementById("barangay_ID");
                    if (barangayEl) {
                        barangayEl.innerHTML = data;
                        barangayEl.disabled = false; // Enable barangay dropdown
                    }
                })
                .catch(err => console.error("Error loading barangays:", err));
        }

        window.addEventListener('DOMContentLoaded', function() {
            const countrySelect = document.getElementById("address_country_ID");
            if (countrySelect && countrySelect.value) {
                toggleAddressFields(countrySelect.value);
            }
        });
    </script>
</head>
<body>
    <div class = "title">
        <h2>Guide Registration</h2>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="success">
            Registration successful! Your guide account has been created.
            <?php if (!empty($_SESSION['assigned_license'])): ?>
                <p>Your Guide License Number: <strong><?= htmlspecialchars($_SESSION['assigned_license']) ?></strong></p>
                <?php unset($_SESSION['assigned_license']); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors["general"])): ?>
        <p class="error"><?= $errors["general"] ?></p>
    <?php endif; ?>

    <form method="POST">
        <h3>Account Info</h3>
        <label for="username">Username</label>
        <input type="text" name="username" id="username" value="<?= $guide["username"] ?? "" ?>">
        <p class="error"><?= $errors["username"] ?? "" ?></p>

        <label for="password">Password</label>
        <input type="password" name="password" id="password">
        <p class="error"><?= $errors["password"] ?? "" ?></p>

        <h3>Basic Info</h3>
        <label for="name_first">First Name</label>
        <input type="text" name="name_first" id="name_first" value="<?= $guide["name_first"] ?? "" ?>">
        <p class="error"><?= $errors["name_first"] ?? "" ?></p>

        <label for="name_second">Second Name (Optional)</label>
        <input type="text" name="name_second" id="name_second" value="<?= $guide["name_second"] ?? "" ?>">
        <p class="error"><?= $errors["name_second"] ?? "" ?></p>

        <label for="name_middle">Middle Name (Optional)</label>
        <input type="text" name="name_middle" id="name_middle" value="<?= $guide["name_middle"] ?? "" ?>">
        <p class="error"><?= $errors["name_middle"] ?? "" ?></p>

        <label for="name_last">Last Name</label>
        <input type="text" name="name_last" id="name_last" value="<?= $guide["name_last"] ?? "" ?>">
        <p class="error"><?= $errors["name_last"] ?? "" ?></p>

        <label for="name_suffix">Suffix (Optional)</label>
        <input type="text" name="name_suffix" id="name_suffix" value="<?= $guide["name_suffix"] ?? "" ?>" placeholder="Jr., Sr., III, etc.">
        <p class="error"><?= $errors["name_suffix"] ?? "" ?></p>

        <label for="contactinfo_email">Email</label>
        <input type="email" name="contactinfo_email" id="contactinfo_email" value="<?= $guide["contactinfo_email"] ?? "" ?>">
        <p class="error"><?= $errors["contactinfo_email"] ?? "" ?></p>

        <label for="person_nationality">Nationality</label>
        <input type="text" name="person_nationality" id="person_nationality" value="<?= $guide["person_nationality"] ?? "" ?>">
        <p class="error"><?= $errors["person_nationality"] ?? "" ?></p>

        <label for="person_gender">Gender</label>
        <select name="person_gender" id="person_gender">
            <option value="">--Select--</option>
            <option value="Male" <?= ($guide["person_gender"] ?? "") === "Male" ? "selected" : "" ?>>Male</option>
            <option value="Female" <?= ($guide["person_gender"] ?? "") === "Female" ? "selected" : "" ?>>Female</option>
        </select>
        <p class="error"><?= $errors["person_gender"] ?? "" ?></p>

        <label for="person_dateofbirth">Date of Birth</label>
        <input type="date" name="person_dateofbirth" id="person_dateofbirth" value="<?= $guide["person_dateofbirth"] ?? "" ?>">
        <p class="error"><?= $errors["person_dateofbirth"] ?? "" ?></p>

        <h3>Guide Information</h3>

        <label>Languages Spoken</label>
        <div class="checkbox-group">
            <?php foreach ($registrationObj->getLanguages() as $r){ ?>
            <label>
                <input type="checkbox" name="languages[]" value="<?= $r['languages_ID']?>" <?= isset($guide["languages"]) && $r['languages_ID'] ? 'checked' : '' ?>>
                <?= $r['languages_name'] ?>
            </label>
            <?php }?>
        </div>
        <p class="error"><?= $errors["languages"] ?? "" ?></p>

        
        <h3>Phone Number</h3>
            <label for="country_ID"> Country Code </label>
            <select name="country_ID" id="country_ID">
                <option value="">--SELECT COUNTRY CODE--</option>

                <?php foreach ($registrationObj->fetchCountryCode() as $country_code){ 
                    $temp = $country_code["country_ID"];
                ?>
                <option value="<?= $temp ?>" <?= ($temp == ($guide["country_ID"] ?? "")) ? "selected" : "" ?>> <?= $country_code["country_name"] ?> <?= $country_code["country_codenumber"]?> </option> 
            <?php } ?>
            </select>
            <p class="errors"> <?= $errors["country_ID"] ?? "" ?> </p>
        
        <label for="phone_number">Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" maxlength="10" inputmode="numeric" pattern="[0-9]*" value = "<?= $guide["phone_number"] ?? "" ?>">
            <p style="color: red; font-weight: bold;"> <?= $errors["phone_number"] ?? "" ?> </p>
            
        <br><br>
        <h3>Emergency Info</h3>
            <label for="emergency_name"> Emergency Name </label>
                <input type="text" name="emergency_name" id="emergency_name" value ="<?= $guide["emergency_name"] ?? "" ?>" >
                <p style="color: red; font-weight: bold;"> <?= $errors["emergency_name"] ?? "" ?> </p>

        <label for="emergency_relationship"> Emergency Relationship </label>
            <input type="text" name="emergency_relationship" id="emergency_relationship" value = "<?= $guide["emergency_relationship"] ?? "" ?>">
            <p style="color: red; font-weight: bold;"> <?= $errors["emergency_relationship"] ?? "" ?> </p>

        
        <label for="emergency_country_ID"> Country Code </label>
        <select name="emergency_country_ID" id="emergency_country_ID">
            <option value="">--SELECT COUNTRY CODE--</option>
            <?php foreach ($registrationObj->fetchCountryCode() as $country_code){ 
                $temp = $country_code["country_ID"];
            ?>
            <option value="<?= $temp ?>" <?= ($temp == ($guide["emergency_country_ID"] ?? "")) ? "selected" : "" ?>> <?= $country_code["country_name"] ?> <?= $country_code["country_codenumber"]?> </option>    

        <?php } ?>
        </select>
        <p class="errors"> <?= $errors["emergency_country_ID"] ?? "" ?> </p>
        

        <label for="emergency_phonenumber">Phone Number</label>
        <input type="text" name="emergency_phonenumber" id="emergency_phonenumber" maxlength="10" inputmode="numeric" pattern="[0-9]*" value = "<?= $guide["emergency_phonenumber"] ?? "" ?>">
        <p style="color: red; font-weight: bold;"> <?= $errors["emergency_phonenumber"] ?? "" ?> </p>


        <h3>Address</h3>

        <label for="address_country_ID"> Country </label>
        <select name="address_country_ID" id="address_country_ID" onchange="toggleAddressFields(this.value)">
            <option value="">--SELECT COUNTRY--</option>
            <?php 
            foreach ($registrationObj->fetchCountry() as $country){ 
                // Debug: Print Philippines ID to HTML comment
                if (stripos($country["country_name"], "Philippines") !== false) {
                    echo "<!-- Philippines country_ID: " . $country["country_ID"] . " -->";
                }
            ?>
                <option value="<?= $country["country_ID"] ?>" 
                    <?= ($country["country_ID"] == ($guide["address_country_ID"] ?? "")) ? "selected" : "" ?>>
                    <?= $country["country_name"] ?>
                </option>
            <?php } ?>
        </select>
        <p class="error"><?= $errors["address_country_ID"] ?? "" ?></p>

        <!-- Region Field - Dropdown for PH, Text Input for others -->
        <div id="region_dropdown_container" style="display: none;">
            <label for="region_ID"> Region </label>
            <select name="region_ID" id="region_ID" onchange="loadProvinces(this.value)" disabled>
                <option value="">--SELECT REGION--</option>
                <?php 
                $regions = $registrationObj->fetchRegion();
                if ($regions && is_array($regions)) {
                    foreach ($regions as $region){ ?>
                        <option value="<?= $region["region_ID"] ?>" 
                            <?= ($region["region_ID"] == ($guide["region_ID"] ?? "")) ? "selected" : "" ?>>
                            <?= $region["region_name"] ?>
                        </option>
                    <?php }
                } ?>
            </select>
            <p class="error"><?= $errors["region_ID"] ?? "" ?></p>
        </div>
        <div id="region_text_container" style="display: none;">
            <label for="region_name"> Region </label>
            <input type="text" name="region_name" id="region_name" value="<?= $guide["region_name"] ?? "" ?>" disabled>
            <p class="error"><?= $errors["region_name"] ?? "" ?></p>
        </div>

        <!-- Province Field - Dropdown for PH, Text Input for others -->
        <div id="province_dropdown_container" style="display: none;">
            <label for="province_ID"> Province </label>
            <select name="province_ID" id="province_ID" onchange="loadCities(this.value)" disabled>
                <option value="">--SELECT PROVINCE--</option>
                <?php 
                $selectedRegion = $guide["region_ID"] ?? "";
                $provinces = $registrationObj->fetchProvince($selectedRegion);
                if ($provinces && is_array($provinces)) {
                    foreach ($provinces as $province){ ?>
                        <option value="<?= $province["province_ID"] ?>" 
                            <?= ($province["province_ID"] == ($guide["province_ID"] ?? "")) ? "selected" : "" ?>>
                            <?= $province["province_name"] ?>
                        </option>
                    <?php }
                } ?>
            </select>
            <p class="error"><?= $errors["province_ID"] ?? "" ?></p>
        </div>
        <div id="province_text_container" style="display: none;">
            <label for="province_name"> Province </label>
            <input type="text" name="province_name" id="province_name" value="<?= $guide["province_name"] ?? "" ?>" disabled>
            <p class="error"><?= $errors["province_name"] ?? "" ?></p>
        </div>

        <!-- City Field - Dropdown for PH, Text Input for others -->
        <div id="city_dropdown_container" style="display: none;">
            <label for="city_ID"> City/Municipality </label>
            <select name="city_ID" id="city_ID" onchange="loadBarangays(this.value)" disabled>
                <option value="">--SELECT CITY--</option>
                <?php
                $selectedProvince = $guide["province_ID"] ?? "";
                $cities = $registrationObj->fetchCity($selectedProvince);
                if ($cities && is_array($cities)) {
                    foreach ($cities as $city){ ?>
                        <option value="<?= $city["city_ID"] ?>" 
                            <?= ($city["city_ID"] == ($guide["city_ID"] ?? "")) ? "selected" : "" ?>>
                            <?= $city["city_name"] ?>
                        </option>
                    <?php }
                } ?>
            </select>
            <p class="error"><?= $errors["city_ID"] ?? "" ?></p>
        </div>
        <div id="city_text_container" style="display: none;">
            <label for="city_name"> City/Municipality </label>
            <input type="text" name="city_name" id="city_name" value="<?= $guide["city_name"] ?? "" ?>" disabled>
            <p class="error"><?= $errors["city_name"] ?? "" ?></p>
        </div>

        <!-- Barangay Field - Dropdown for PH, Text Input for others -->
        <div id="barangay_dropdown_container" style="display: none;">
            <label for="barangay_ID"> Barangay </label>
            <select name="barangay_ID" id="barangay_ID" disabled>
                <option value="">--SELECT BARANGAY--</option>
                <?php
                $selectedCity = $guide["city_ID"] ?? "";
                $barangays = $registrationObj->fetchBarangay($selectedCity);
                if ($barangays && is_array($barangays)) {
                    foreach ($barangays as $barangay){ ?>
                        <option value="<?= $barangay["barangay_ID"] ?>" 
                            <?= ($barangay["barangay_ID"] == ($guide["barangay_ID"] ?? "")) ? "selected" : "" ?>>
                            <?= $barangay["barangay_name"] ?>
                        </option>
                    <?php }
                } ?>
            </select>
            <p class="error"><?= $errors["barangay_ID"] ?? "" ?></p>
        </div>
        <div id="barangay_text_container" style="display: none;">
            <label for="barangay_name"> Barangay </label>
            <input type="text" name="barangay_name" id="barangay_name" value="<?= $guide["barangay_name"] ?? "" ?>" disabled>
            <p class="error"><?= $errors["barangay_name"] ?? "" ?></p>
        </div>

        <label for="address_street"> Street </label>
        <input type="text" name="address_street" id="address_street" value="<?= $guide["address_street"] ?? "" ?>">
        <p class="error"><?= $errors["address_street"] ?? "" ?></p>

        <label for="address_houseno"> House No</label>
        <input type="text" name="address_houseno" id="address_houseno" value="<?= $guide["address_houseno"] ?? "" ?>">
        <p class="error"><?= $errors["address_houseno"] ?? "" ?></p>

        <button type="submit">Register</button>
    </form>

</body>

</html>
