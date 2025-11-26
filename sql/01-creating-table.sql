--  Country
CREATE TABLE Country (
    country_ID INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL UNIQUE,
    country_codename VARCHAR(10),
    country_codenumber VARCHAR(10)
);


--  Phone Number
CREATE TABLE Phone_Number (
    phone_ID INT AUTO_INCREMENT PRIMARY KEY,
    country_ID INT,
    phone_number VARCHAR(15) NOT NULL ,
    FOREIGN KEY (country_ID) REFERENCES Country(country_ID),
    UNIQUE KEY unique_phone_per_country (country_ID, phone_number)
);

--  Region 
CREATE TABLE Region (
    region_ID INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(100) NOT NULL,
    country_ID INT NOT NULL,
    FOREIGN KEY (country_ID) REFERENCES Country(country_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (region_name, country_ID)
);

--  Province
CREATE TABLE Province (
    province_ID INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    region_ID INT NOT NULL,
    FOREIGN KEY (region_ID) REFERENCES Region(region_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_province_per_region (province_name, region_ID)
);

--  City
CREATE TABLE City (
    city_ID INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    province_ID INT NOT NULL,
    FOREIGN KEY (province_ID) REFERENCES Province(province_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_city_per_province (city_name, province_ID)
);

--  Barangay
CREATE TABLE Barangay (
    barangay_ID INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    city_ID INT NOT NULL,
    FOREIGN KEY (city_ID) REFERENCES City(city_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_barangay_per_city (barangay_name, city_ID)
);

--  Address
CREATE TABLE Address_Info (
    address_ID INT AUTO_INCREMENT PRIMARY KEY,
    address_houseno VARCHAR(50) NOT NULL,
    address_street VARCHAR(50) NOT NULL,
    barangay_ID INT NOT NULL,
    FOREIGN KEY (barangay_ID) REFERENCES Barangay(barangay_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_full_address (
        address_houseno,
        address_street,
        barangay_ID
    )
);

--  Emergency Contact Info
CREATE TABLE Emergency_Info (
    emergency_ID INT AUTO_INCREMENT PRIMARY KEY,
    emergency_Name VARCHAR(225) NOT NULL,
    emergency_Relationship VARCHAR(225) NOT NULL,
    phone_ID INT,
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID)
);

--  Contact Info
CREATE TABLE Contact_Info (
    contactinfo_ID INT AUTO_INCREMENT PRIMARY KEY,
    address_ID INT,
    phone_ID INT,
    contactinfo_email VARCHAR(100) NOT NULL,
    emergency_ID INT,
    FOREIGN KEY (address_ID) REFERENCES Address_Info(address_ID),
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID),
    FOREIGN KEY (emergency_ID) REFERENCES Emergency_Info(emergency_ID)
);

--  Name Info
CREATE TABLE Name_Info (
    name_ID INT AUTO_INCREMENT PRIMARY KEY,
    name_first VARCHAR(100) NOT NULL,
    name_second VARCHAR(225),
    name_middle VARCHAR(225),
    name_last VARCHAR(225) NOT NULL,
    name_suffix VARCHAR(225)
);

--  Person
CREATE TABLE Person (
    person_ID INT AUTO_INCREMENT PRIMARY KEY,
    name_ID INT,
    contactinfo_ID INT,
    person_Nationality VARCHAR(225),
    person_Gender VARCHAR(225),
    person_DateOfBirth DATE,
    FOREIGN KEY (name_ID) REFERENCES Name_Info(name_ID),
    FOREIGN KEY (contactinfo_ID) REFERENCES Contact_Info(contactinfo_ID)
);


--  User
CREATE TABLE User_Login(
    user_ID INT AUTO_INCREMENT PRIMARY KEY,
    person_ID INT,
    user_username VARCHAR(100) NOT NULL UNIQUE,
    user_password VARCHAR(255) NOT NULL,
    FOREIGN KEY (person_ID) REFERENCES Person(person_ID)
);

--  Role 
CREATE TABLE Role (
    role_ID INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
);

--  Account Info
CREATE TABLE Account_Info (
    account_ID INT AUTO_INCREMENT PRIMARY KEY,
    user_ID INT,
    role_ID INT,
    account_status ENUM('Active', 'Suspended', 'Pending'),
    account_rating_score DECIMAL(3,2) DEFAULT 0.00,
    account_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_ID) REFERENCES User_Login(user_ID),
    FOREIGN KEY (role_ID) REFERENCES Role(role_ID)
);

CREATE TABLE Action (
    action_ID INT AUTO_INCREMENT PRIMARY KEY,
    action_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Activity_Log (
    activity_ID INT AUTO_INCREMENT PRIMARY KEY,
    account_ID INT,
    action_ID INT,
    activity_description TEXT,
    activity_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (action_ID) REFERENCES Action(action_ID)
);

CREATE TABLE Activity_View(
    activity_ID INT NOT NULL, 
    account_ID INT NOT NULL,
    activity_isViewed TINYINT DEFAULT 0,
    
    PRIMARY KEY (account_ID, activity_ID),
    
    FOREIGN KEY (account_ID) 
        REFERENCES Account_Info(account_ID) 
        ON DELETE CASCADE, -- Recommended for junction tables
    
    FOREIGN KEY (activity_ID) 
        REFERENCES Activity_Log(activity_ID)
        ON DELETE CASCADE  -- Recommended for junction tables
);

--  ==============================
--  ADMIN SYSTEM TABLES
--  ==============================

CREATE TABLE Admin(
    admin_ID INT AUTO_INCREMENT PRIMARY KEY,
    account_ID INT,
    admin_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID)
);

--  ==============================
--  GUIDE SYSTEM TABLES
-- ============================== 

CREATE TABLE Guide_License(
    lisence_ID INT AUTO_INCREMENT PRIMARY KEY,
    lisence_number VARCHAR(100) NOT NULL UNIQUE, -- create a function for this 
    lisence_created_date CURRENT_TIMESTAMP, -- created a time current timestamp
    lisence_issued_date DATE, -- this one if an admin accepted it 
    lisence_issued_by VARCHAR(225), -- an admin
    lisence_expiry_date DATE, 
    lisence_verification_status VARCHAR(50) NOT NULL, -- Pendin when guide was created 
    lisence_status VARCHAR(50) NOT NULL
);

CREATE TABLE Languages(
    languages_ID INT AUTO_INCREMENT PRIMARY KEY,
    language_name ENUM('English', 'Chavacano', 'Filipino') NOT NULL UNIQUE
);

CREATE TABLE Guide(
    guide_ID INT AUTO_INCREMENT PRIMARY KEY,
    account_ID INT,
    lisence_ID INT,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (lisence_ID) REFERENCES Guide_License(lisence_ID)
);

CREATE TABLE Guide_Languages(
    guide_ID INT,
    languages_ID INT,
    PRIMARY KEY (guide_ID, languages_ID),
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID) ON DELETE CASCADE,
    FOREIGN KEY (languages_ID) REFERENCES Languages(languages_ID) ON DELETE CASCADE
);


-- ==============================
--  SCHEDULE SYSTEM TABLES
-- ==============================

CREATE TABLE Pricing (
    pricing_ID INT AUTO_INCREMENT PRIMARY KEY,
    pricing_currency VARCHAR(10) NOT NULL,
    pricing_foradult DECIMAL(10,2) NOT NULL,
    pricing_forchild DECIMAL(10,2),
    pricing_foryoungadult DECIMAL(10,2),
    pricing_forsenior DECIMAL(10,2),
    pricing_forpwd DECIMAL(10,2),
    include_meal BOOLEAN DEFAULT FALSE,
    pricing_mealfee DECIMAL(10,2) DEFAULT 0.00,
    transport_fee DECIMAL(10,2) DEFAULT 0.00,
    pricing_discount DECIMAL(10,2) NOT NULL
);


CREATE TABLE Number_Of_People(
    numberofpeople_ID INT AUTO_INCREMENT PRIMARY KEY,
    pricing_ID INT,
    numberofpeople_maximum INT NOT NULL,
    numberofpeople_based VARCHAR(50) NOT NULL,
    FOREIGN KEY (pricing_ID) REFERENCES Pricing(pricing_ID)
);

CREATE TABLE Schedule(
    schedule_ID INT AUTO_INCREMENT PRIMARY KEY,
    numberofpeople_ID INT,
    schedule_days INT NOT NULL DEFAULT 1,
    FOREIGN KEY (numberofpeople_ID) REFERENCES Number_Of_People(numberofpeople_ID)    
);

-- ==============================
--  TOURIST SPOTS SYSTEM TABLES
-- ==============================

--  Tour Spot
CREATE TABLE Tour_Spots(
    spots_ID INT AUTO_INCREMENT PRIMARY KEY,
    spots_name VARCHAR(225) NOT NULL,
    spots_category  VARCHAR(225) NOT NULL,
    spots_description TEXT,
    spots_address VARCHAR(500) NOT NULL,
    spots_googlelink VARCHAR(500)

);


CREATE TABLE Tour_Spots_Images(
    spotsimage_ID INT AUTO_INCREMENT PRIMARY KEY,
    spotsimage_PATH VARCHAR(500) NOT NULL,
    spots_ID INT,
    FOREIGN KEY (spots_ID) REFERENCES Tour_Spots(spots_ID)
);


--  Tour Packages
CREATE TABLE Tour_Package(
    tourpackage_ID INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_name VARCHAR(225) NOT NULL,
    tourpackage_desc TEXT,
    guide_ID INT,
    schedule_ID INT,
    FOREIGN KEY (schedule_ID) REFERENCES Schedule(schedule_ID),
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID)
);

--  Tour Package Spots (Many-to-Many Relationship)
CREATE TABLE Tour_Package_Spots (
    packagespot_ID INT AUTO_INCREMENT PRIMARY KEY, 
    tourpackage_ID INT NOT NULL,
    spots_ID INT NULL,
    packagespot_activityname VARCHAR(255) NULL,
    packagespot_starttime TIME NULL,
    packagespot_endtime TIME NULL,
    packagespot_day INT NOT NULL,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID) ON DELETE CASCADE,
    FOREIGN KEY (spots_ID) REFERENCES Tour_Spots(spots_ID) ON DELETE SET NULL
);


CREATE TABLE Request_Package(
    request_ID INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_ID INT,
    request_status VARCHAR(50) NOT NULL,
    rejection_reason TEXT,
    request_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    request_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID)
);



-- ==============================
--  BOOKING SYSTEM TABLES
-- ==============================

CREATE TABLE Booking(
    booking_ID INT AUTO_INCREMENT PRIMARY KEY,
    tourist_ID INT,
    booking_status ENUM('Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress', 'Completed', 'Cancelled', 'Refunded', 'Failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending for Approval',
    booking_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tourpackage_ID INT,
    booking_start_date DATE NOT NULL,
    booking_end_date DATE NOT NULL,
    itinerary_sent TINYINT(1) DEFAULT 0,
    itinerary_sent_at DATETIME NULL
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID),
    FOREIGN KEY (tourist_ID) REFERENCES Account_Info(account_ID)
);

CREATE TABLE Companion_Category(
    companion_category_ID INT AUTO_INCREMENT PRIMARY KEY,
    companion_category_name ENUM('Adult', 'Children', 'Senior', 'PWD') NOT NULL UNIQUE
);

CREATE TABLE Companion(
    companion_ID INT AUTO_INCREMENT PRIMARY KEY,
    companion_name VARCHAR(225) NOT NULL,
    companion_age INT NOT NULL,
    companion_category_ID INT,
    FOREIGN KEY (companion_category_ID) REFERENCES Companion_Category(companion_category_ID)
);

CREATE TABLE Booking_Bundle(
    bookingbundle_ID INT AUTO_INCREMENT PRIMARY KEY,
    booking_ID INT,
    companion_ID INT,
    FOREIGN KEY (booking_ID) REFERENCES Booking(booking_ID),
    FOREIGN KEY (companion_ID) REFERENCES Companion(companion_ID)
);


-- ==============================
--  PAYMENT SYSTEM TABLES
-- ==============================

CREATE TABLE Payment_Info(
    paymentinfo_ID INT AUTO_INCREMENT PRIMARY KEY,
    booking_ID INT,
    paymentinfo_total_amount DECIMAL(10,2) NOT NULL,
    paymentinfo_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_ID) REFERENCES Booking(booking_ID)
);

CREATE TABLE Method_Category(
    methodcategory_ID INT AUTO_INCREMENT PRIMARY KEY,
    methodcategory_name VARCHAR(100) NOT NULL UNIQUE,
    methodcategory_type VARCHAR(100) NOT NULL,
    methodcategory_processing_fee DECIMAL(10,2) NOT NULL,
    methodcategory_is_active BOOLEAN DEFAULT TRUE
);


CREATE TABLE Method (
    method_ID INT AUTO_INCREMENT PRIMARY KEY,
    methodcategory_ID INT,

    -- Payment / Card Info
    method_amount DECIMAL(10,2) DEFAULT NULL,
    method_currency VARCHAR(10) DEFAULT NULL,
    method_cardnumber VARCHAR(20) DEFAULT NULL,
    method_expmonth VARCHAR(2) DEFAULT NULL,
    method_expyear VARCHAR(4) DEFAULT NULL,
    method_cvc VARCHAR(4) DEFAULT NULL,

    -- Billing Info
    method_name VARCHAR(100) NOT NULL,
    method_email VARCHAR(100) NOT NULL,
    method_line1 VARCHAR(150) DEFAULT NULL,
    method_city VARCHAR(100) DEFAULT NULL,
    method_postalcode VARCHAR(20) DEFAULT NULL,
    method_country VARCHAR(10) DEFAULT NULL,

    -- Status / Timestamps
    method_status ENUM('Active', 'Inactive') DEFAULT 'Active',
    method_created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    phone_ID INT,
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number (phone_ID),
    -- Foreign Key
    CONSTRAINT fk_method_category FOREIGN KEY (methodcategory_ID)
        REFERENCES Method_Category(methodcategory_ID)
);




CREATE TABLE Payment_Transaction(
    transaction_ID INT AUTO_INCREMENT PRIMARY KEY,
    paymentinfo_ID INT,
    method_ID INT,
    transaction_status VARCHAR(50) NOT NULL,
    transaction_reference VARCHAR(100) NOT NULL UNIQUE,
    transaction_created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paymongo_intent_id VARCHAR(100) NULL,
    paymongo_refund_id VARCHAR(100) NULL,
    FOREIGN KEY (paymentinfo_ID) REFERENCES Payment_Info(paymentinfo_ID),
    FOREIGN KEY (method_ID) REFERENCES Method(method_ID)
);

CREATE TABLE CategoryRefund_Name(
    categoryrefundname_ID AUTO_INCREMENT PRIMARY KEY,
    categoryrefundname_name VARCHAR(225)
);

CREATE TABLE Category_Refund (
    categoryrefund_ID AUTO_INCREMENT PRIMARY KEY,
    categoryrefundname_ID INT,
    role_ID INT,
    FOREIGN KEY (categoryrefundname_ID) REFERENCES CategoryRefund_Name(categoryrefundname_ID),
    FOREIGN KEY (role_ID) REFERENCES Role(role_ID)
);

CREATE TABLE Refund(
    refund_ID INT AUTO_INCREMENT PRIMARY KEY,
    paymongo_refund_id VARCHAR(100) NULL,
    transaction_ID INT,
    categoryrefund_ID INT,
    refund_reason TEXT,
    refund_status VARCHAR(50) NOT NULL,
    refund_requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    refund_approval_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    refund_processingfee DECIMAL(10,2),
    refund_refundfee DECIMAL(10,2),
    refund_total_amount DECIMAL(10,2),
    FOREIGN KEY (transaction_ID) REFERENCES Payment_Transaction(transaction_ID),
    FOREIGN KEY (categoryrefund_ID) REFERENCES Category_Refund(categoryrefund_ID)
);



-- ==============================
--  RATING SYSTEM TABLES
-- ==============================

CREATE TABLE Rating(
    rating_ID INT AUTO_INCREMENT PRIMARY KEY,
    rater_account_ID INT,
    rating_type ENUM('Tourist', 'Guide', 'Tour Spots', 'Tour Package') NOT NULL,
    rating_account_ID INT,
    rating_tourpackage_ID INT,
    rating_tourspots_ID INT,
    rating_value DECIMAL(2,1) NOT NULL,
    rating_description TEXT,
    rating_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rater_account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (rating_account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (rating_tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID),
    FOREIGN KEY (rating_tourspots_ID) REFERENCES Tour_Spots(spots_ID)
);
 
 CREATE TABLE Review_Image(
    review_ID INT AUTO_INCREMENT PRIMARY KEY,
    rating_ID INT,
    review_image_path VARCHAR(255) NOT NULL,
    review_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rating_ID) REFERENCES Rating(rating_ID)
 );

-- ==============================
--  VIEW SYSTEM TABLES
-- ==============================

CREATE VIEW View_Receipt_Booking AS
SELECT 
    -- Booking Details
    b.booking_ID,
    b.booking_status,
    b.booking_created_at,
    b.booking_start_date,
    b.booking_end_date,

    -- Tourist (Customer)
    ai.account_ID AS tourist_account_ID,
    CONCAT(ni.name_first, 
           IF(ni.name_middle IS NOT NULL AND ni.name_middle != '', CONCAT(' ', ni.name_middle), ''), 
           ' ', ni.name_last) AS tourist_fullname,
    ci.contactinfo_email AS tourist_email,

    -- Tour Package Info
    tp.tourpackage_ID,
    tp.tourpackage_name,
    tp.tourpackage_desc,
    
    -- Guide Info
    g.guide_ID,
    CONCAT(gn.name_first, ' ', gn.name_last) AS guide_fullname,
    
    -- Payment Info
    pi.paymentinfo_ID,
    pi.paymentinfo_total_amount,
    pi.paymentinfo_date,
    
    -- Payment Transaction
    pt.transaction_ID,
    pt.method_ID,
    m.method_name,
    m.method_type,
    m.method_processing_fee,
    pt.transaction_status,
    pt.transaction_reference,
    pt.transaction_created_date,
    pt.transaction_updated_date,
    
    -- Refund Info (if any)
    r.refund_ID,
    r.refund_reason,
    r.refund_status,
    r.refund_requested_date,
    r.refund_approval_date,
    r.refund_processingfee,
    r.refund_refundfee,
    r.refund_total_amount,

    -- Computed Fields (for display)
    (pi.paymentinfo_total_amount - IFNULL(r.refund_total_amount, 0)) AS final_paid_amount

    FROM Booking b
        -- Tourist Info
        JOIN Account_Info ai ON b.tourist_ID = ai.account_ID
        JOIN User_Login ul ON ai.user_ID = ul.user_ID
        JOIN Person p ON ul.person_ID = p.person_ID
        JOIN Name_Info ni ON p.name_ID = ni.name_ID
        JOIN Contact_Info ci ON p.contactinfo_ID = ci.contactinfo_ID
        
        -- Tour Package Info
        JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
        LEFT JOIN Guide g ON tp.guide_ID = g.guide_ID
        LEFT JOIN Account_Info gai ON g.account_ID = gai.account_ID
        LEFT JOIN User_Login gul ON gai.user_ID = gul.user_ID
        LEFT JOIN Person gp ON gul.person_ID = gp.person_ID
        LEFT JOIN Name_Info gn ON gp.name_ID = gn.name_ID
        
        -- Payment Info & Transaction
        LEFT JOIN Payment_Info pi ON b.booking_ID = pi.booking_ID
        LEFT JOIN Payment_Transaction pt ON pi.paymentinfo_ID = pt.paymentinfo_ID
        LEFT JOIN Method m ON pt.method_ID = m.method_ID

        -- Refund Info (optional)
        LEFT JOIN Refund r ON pt.transaction_ID = r.transaction_ID;






