-- ===============================
-- COUNTRY
-- ===============================
CREATE TABLE Country (
    country_ID         INT AUTO_INCREMENT PRIMARY KEY,
    country_name       VARCHAR(100) NOT NULL UNIQUE,
    country_codename   VARCHAR(10),
    country_codenumber VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- PHONE NUMBER
-- ===============================
CREATE TABLE Phone_Number (
    phone_ID     INT AUTO_INCREMENT PRIMARY KEY,
    country_ID   INT,
    phone_number VARCHAR(15) NOT NULL,
    FOREIGN KEY (country_ID) REFERENCES Country(country_ID),
    UNIQUE KEY unique_phone_per_country (country_ID, phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- REGION
-- ===============================
CREATE TABLE Region (
    region_ID   INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(100) NOT NULL,
    country_ID  INT NOT NULL,
    FOREIGN KEY (country_ID) REFERENCES Country(country_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (region_name, country_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- PROVINCE
-- ===============================
CREATE TABLE Province (
    province_ID   INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    region_ID     INT NOT NULL,
    FOREIGN KEY (region_ID) REFERENCES Region(region_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_province_per_region (province_name, region_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- CITY
-- ===============================
CREATE TABLE City (
    city_ID      INT AUTO_INCREMENT PRIMARY KEY,
    city_name    VARCHAR(100) NOT NULL,
    province_ID  INT NOT NULL,
    FOREIGN KEY (province_ID) REFERENCES Province(province_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_city_per_province (city_name, province_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- BARANGAY
-- ===============================
CREATE TABLE Barangay (
    barangay_ID   INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    city_ID       INT NOT NULL,
    FOREIGN KEY (city_ID) REFERENCES City(city_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_barangay_per_city (barangay_name, city_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ADDRESS INFO
-- ===============================
CREATE TABLE Address_Info (
    address_ID      INT AUTO_INCREMENT PRIMARY KEY,
    address_houseno VARCHAR(50) NOT NULL,
    address_street  VARCHAR(50) NOT NULL,
    barangay_ID     INT NOT NULL,
    FOREIGN KEY (barangay_ID) REFERENCES Barangay(barangay_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_full_address (address_houseno, address_street, barangay_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- EMERGENCY CONTACT INFO
-- ===============================
CREATE TABLE Emergency_Info (
    emergency_ID          INT AUTO_INCREMENT PRIMARY KEY,
    emergency_Name        VARCHAR(225) NOT NULL,
    emergency_Relationship VARCHAR(225) NOT NULL,
    phone_ID              INT,
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- CONTACT INFO
-- ===============================
CREATE TABLE Contact_Info (
    contactinfo_ID INT AUTO_INCREMENT PRIMARY KEY,
    address_ID     INT,
    phone_ID       INT,
    contactinfo_email VARCHAR(100) NOT NULL,
    emergency_ID   INT,
    FOREIGN KEY (address_ID) REFERENCES Address_Info(address_ID),
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID),
    FOREIGN KEY (emergency_ID) REFERENCES Emergency_Info(emergency_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- NAME INFO
-- ===============================
CREATE TABLE Name_Info (
    name_ID     INT AUTO_INCREMENT PRIMARY KEY,
    name_first   VARCHAR(100) NOT NULL,
    name_second  VARCHAR(225),
    name_middle  VARCHAR(225),
    name_last    VARCHAR(225) NOT NULL,
    name_suffix  VARCHAR(225)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- PERSON
-- ===============================
CREATE TABLE Person (
    person_ID         INT AUTO_INCREMENT PRIMARY KEY,
    name_ID           INT,
    contactinfo_ID    INT,
    person_isPWD      TINYINT(4) NOT NULL DEFAULT 0,
    person_Nationality VARCHAR(225),
    person_Gender     VARCHAR(225),
    person_DateOfBirth DATE,
    FOREIGN KEY (name_ID) REFERENCES Name_Info(name_ID),
    FOREIGN KEY (contactinfo_ID) REFERENCES Contact_Info(contactinfo_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- USER LOGIN
-- ===============================
CREATE TABLE User_Login (
    user_ID       INT AUTO_INCREMENT PRIMARY KEY,
    person_ID     INT,
    user_username VARCHAR(100) NOT NULL UNIQUE,
    user_password VARCHAR(255) NOT NULL,
    FOREIGN KEY (person_ID) REFERENCES Person(person_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ROLE
-- ===============================
CREATE TABLE Role (
    role_ID   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ACCOUNT INFO
-- ===============================
CREATE TABLE Account_Info (
    account_ID           INT AUTO_INCREMENT PRIMARY KEY,
    user_ID              INT,
    role_ID              INT,
    account_status       ENUM('Active','Suspended','Pending') DEFAULT 'Pending',
    account_rating_score DECIMAL(3,2) DEFAULT 0.00,
    account_created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    account_profilepic   VARCHAR(500) DEFAULT NULL, 
    account_aboutme      TEXT DEFAULT NULL, 
    account_bio          VARCHAR(255) DEFAULT NULL,
    account_nickname     VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (user_ID) REFERENCES User_Login(user_ID) ON DELETE CASCADE,
    FOREIGN KEY (role_ID) REFERENCES Role(role_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ACTION
-- ===============================
CREATE TABLE Action (
    action_ID   INT AUTO_INCREMENT PRIMARY KEY,
    action_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ACTIVITY LOG
-- ===============================
CREATE TABLE Activity_Log (
    activity_ID          INT AUTO_INCREMENT PRIMARY KEY,
    account_ID           INT,
    action_ID            INT,
    activity_description TEXT,
    activity_timestamp   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (action_ID) REFERENCES Action(action_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ACTIVITY VIEW
-- ===============================
CREATE TABLE Activity_View (
    activity_ID       INT NOT NULL,
    account_ID        INT NOT NULL,
    activity_isViewed TINYINT DEFAULT 0,
    PRIMARY KEY (account_ID, activity_ID),
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID) ON DELETE CASCADE,
    FOREIGN KEY (activity_ID) REFERENCES Activity_Log(activity_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- ADMIN
-- ===============================
CREATE TABLE Admin (
    admin_ID         INT AUTO_INCREMENT PRIMARY KEY,
    account_ID       INT,
    admin_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- GUIDE LICENSE
-- ===============================
CREATE TABLE Guide_License (
    license_ID                  INT AUTO_INCREMENT PRIMARY KEY,
    license_number              VARCHAR(100) NOT NULL UNIQUE,
    license_created_date        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    license_issued_date         DATE,
    license_issued_by           VARCHAR(225),
    license_expiry_date         DATE,
    license_verification_status ENUM('Pending','Revoked','Verified','Active','Expired') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- LANGUAGES
-- ===============================
CREATE TABLE Languages (
    languages_ID   INT AUTO_INCREMENT PRIMARY KEY,
    languages_name ENUM('English','Chavacano','Filipino') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- GUIDE
-- ===============================
CREATE TABLE Guide (
    guide_ID   INT AUTO_INCREMENT PRIMARY KEY,
    account_ID INT,
    license_ID INT,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (license_ID) REFERENCES Guide_License(license_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- GUIDE LANGUAGES
-- ===============================
CREATE TABLE Guide_Languages (
    guide_ID     INT NOT NULL,
    languages_ID INT NOT NULL,
    PRIMARY KEY (guide_ID, languages_ID),
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID) ON DELETE CASCADE,
    FOREIGN KEY (languages_ID) REFERENCES Languages(languages_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- PRICING
-- ===============================
CREATE TABLE Pricing (
    pricing_ID            INT AUTO_INCREMENT PRIMARY KEY,
    pricing_currency      VARCHAR(10) NOT NULL,
    pricing_foradult      DECIMAL(10,2) NOT NULL,
    pricing_forchild      DECIMAL(10,2),
    pricing_foryoungadult DECIMAL(10,2),
    pricing_forsenior     DECIMAL(10,2),
    pricing_forpwd        DECIMAL(10,2),
    include_meal          TINYINT(1) DEFAULT 0,
    pricing_mealfee       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    transport_fee         DECIMAL(10,2) DEFAULT 0.00,
    pricing_discount      DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- NUMBER OF PEOPLE
-- ===============================
CREATE TABLE Number_Of_People (
    numberofpeople_ID      INT AUTO_INCREMENT PRIMARY KEY,
    pricing_ID             INT,
    numberofpeople_maximum INT NOT NULL,
    numberofpeople_based   VARCHAR(50) NOT NULL,
    FOREIGN KEY (pricing_ID) REFERENCES Pricing(pricing_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- SCHEDULE
-- ===============================
CREATE TABLE Schedule (
    schedule_ID       INT AUTO_INCREMENT PRIMARY KEY,
    numberofpeople_ID INT,
    schedule_days     INT NOT NULL DEFAULT 1,
    FOREIGN KEY (numberofpeople_ID) REFERENCES Number_Of_People(numberofpeople_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- TOUR SPOTS
-- ===============================
CREATE TABLE Tour_Spots (
    spots_ID          INT AUTO_INCREMENT PRIMARY KEY,
    spots_name        VARCHAR(225) NOT NULL,
    spots_category    VARCHAR(225) NOT NULL,
    spots_description TEXT,
    spots_address     VARCHAR(500) NOT NULL,
    spots_googlelink  VARCHAR(500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- TOUR SPOTS IMAGES
-- ===============================
CREATE TABLE Tour_Spots_Images (
    spotsimage_ID  INT AUTO_INCREMENT PRIMARY KEY,
    spotsimage_PATH VARCHAR(500) NOT NULL,
    spots_ID       INT,
    FOREIGN KEY (spots_ID) REFERENCES Tour_Spots(spots_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- TOUR PACKAGE
-- ===============================
CREATE TABLE Tour_Package (
    tourpackage_ID     INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_name   VARCHAR(225) NOT NULL,
    tourpackage_desc   TEXT,
    tourpackage_status ENUM('Active','Inactive','Deleted','Rejected by the Admin') NOT NULL DEFAULT 'Active',
    guide_ID           INT,
    schedule_ID        INT,
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (schedule_ID) REFERENCES Schedule(schedule_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- TOUR PACKAGE SPOTS
-- ===============================
CREATE TABLE Tour_Package_Spots (
    packagespot_ID          INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_ID          INT NOT NULL,
    spots_ID                INT,
    packagespot_activityname VARCHAR(255),
    packagespot_starttime    TIME,
    packagespot_endtime      TIME,
    packagespot_day          INT NOT NULL DEFAULT 1,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID) ON DELETE CASCADE,
    FOREIGN KEY (spots_ID) REFERENCES Tour_Spots(spots_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- REQUEST PACKAGE
-- ===============================
CREATE TABLE Request_Package (
    request_ID         INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_ID     INT,
    request_status     VARCHAR(50) NOT NULL,
    rejection_reason   TEXT,
    request_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- COMPANION CATEGORY
-- ===============================
CREATE TABLE Companion_Category (
    companion_category_ID   INT AUTO_INCREMENT PRIMARY KEY,
    companion_category_name ENUM('Infant','Child','Young Adult','Adult','Senior','PWD') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- COMPANION
-- ===============================
CREATE TABLE Companion (
    companion_ID           INT AUTO_INCREMENT PRIMARY KEY,
    companion_name         VARCHAR(225) NOT NULL,
    companion_age          INT,
    companion_category_ID  INT,
    FOREIGN KEY (companion_category_ID) REFERENCES Companion_Category(companion_category_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- BOOKING
-- ===============================
CREATE TABLE Booking (
    booking_ID         INT AUTO_INCREMENT PRIMARY KEY,
    tourist_ID         INT,
    booking_isselfincluded TINYINT(4) DEFAULT 0,
    booking_status     ENUM('Pending for Payment','Pending for Approval','Approved','In Progress','Completed','Cancelled','Cancelled - No Refund','Refunded','Failed','Rejected by the Guide','Booking Expired — Payment Not Completed','Booking Expired — Guide Did Not Confirm in Time') NOT NULL DEFAULT 'Pending for Payment',
    booking_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tourpackage_ID     INT,
    booking_start_date DATE NOT NULL,
    booking_end_date   DATE NOT NULL,
    booking_meeting_ID INT DEFAULT NULL,
    booking_custom_meeting VARCHAR(255) DEFAULT NULL,
    itinerary_sent     TINYINT(1) DEFAULT 0,
    itinerary_sent_at  DATETIME,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID),
    FOREIGN KEY (tourist_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (booking_meeting_ID) REFERENCES Meeting_Point(meeting_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Meeting_Point (
    meeting_ID          INT AUTO_INCREMENT PRIMARY KEY,
    guide_ID            INT NOT NULL,
    meeting_name        VARCHAR(100) NOT NULL,
    meeting_description VARCHAR(255),
    meeting_address     VARCHAR(500),
    meeting_googlelink  VARCHAR(500),
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_meeting_per_guide (meeting_name, guide_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- ===============================
-- BOOKING BUNDLE
-- ===============================
CREATE TABLE Booking_Bundle (
    bookingbundle_ID INT AUTO_INCREMENT PRIMARY KEY,
    booking_ID       INT,
    companion_ID     INT,
    FOREIGN KEY (booking_ID) REFERENCES Booking(booking_ID),
    FOREIGN KEY (companion_ID) REFERENCES Companion(companion_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- PAYMENT INFO
-- ===============================
CREATE TABLE Payment_Info (
    paymentinfo_ID       INT AUTO_INCREMENT PRIMARY KEY,
    booking_ID           INT,
    paymentinfo_total_amount DECIMAL(10,2) NOT NULL,
    paymentinfo_date     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_ID) REFERENCES Booking(booking_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- METHOD CATEGORY
-- ===============================
CREATE TABLE Method_Category (
    methodcategory_ID           INT AUTO_INCREMENT PRIMARY KEY,
    methodcategory_name         VARCHAR(100) NOT NULL UNIQUE,
    methodcategory_type         VARCHAR(100) NOT NULL,
    methodcategory_processing_fee DECIMAL(10,2) NOT NULL,
    methodcategory_is_active    TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- METHOD
-- ===============================
CREATE TABLE Method (
    method_ID          INT AUTO_INCREMENT PRIMARY KEY,
    methodcategory_ID  INT,
    method_amount      DECIMAL(10,2),
    method_currency    VARCHAR(10),
    method_cardnumber  VARCHAR(20),
    method_expmonth    VARCHAR(2),
    method_expyear     VARCHAR(4),
    method_cvc         VARCHAR(4),
    method_name        VARCHAR(100) NOT NULL,
    method_email       VARCHAR(100) NOT NULL,
    method_line1       VARCHAR(150),
    method_city        VARCHAR(100),
    method_postalcode  VARCHAR(20),
    method_country     VARCHAR(10),
    method_status      ENUM('Active','Inactive') DEFAULT 'Active',
    method_created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    phone_ID           INT,
    FOREIGN KEY (methodcategory_ID) REFERENCES Method_Category(methodcategory_ID),
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- PAYMENT TRANSACTION
-- ===============================
CREATE TABLE Payment_Transaction (
    transaction_ID           INT AUTO_INCREMENT PRIMARY KEY,
    paymongo_intent_id       VARCHAR(100),
    paymentinfo_ID           INT,
    method_ID                INT,
    transaction_status       VARCHAR(50),
    transaction_reference    VARCHAR(100) UNIQUE,
    transaction_created_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    transaction_updated_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paymongo_refund_id       VARCHAR(100),
    FOREIGN KEY (paymentinfo_ID) REFERENCES Payment_Info(paymentinfo_ID),
    FOREIGN KEY (method_ID) REFERENCES Method(method_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- CATEGORY REFUND NAME
-- ===============================
CREATE TABLE CategoryRefund_Name (
    categoryrefundname_ID INT AUTO_INCREMENT PRIMARY KEY,
    categoryrefundname_name VARCHAR(225)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- CATEGORY REFUND
-- ===============================
CREATE TABLE Category_Refund (
    categoryrefund_ID     INT AUTO_INCREMENT PRIMARY KEY,
    categoryrefundname_ID INT,
    role_ID               INT,
    FOREIGN KEY (categoryrefundname_ID) REFERENCES CategoryRefund_Name(categoryrefundname_ID),
    FOREIGN KEY (role_ID) REFERENCES Role(role_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- REFUND
-- ===============================
CREATE TABLE Refund (
    refund_ID             INT AUTO_INCREMENT PRIMARY KEY,
    paymongo_refund_id    VARCHAR(100),
    transaction_ID        INT,
    categoryrefund_ID     INT,
    refund_reason         TEXT,
    refund_status         VARCHAR(50) NOT NULL,
    refund_requested_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    refund_approval_date  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    refund_processingfee  DECIMAL(10,2),
    refund_refundfee      DECIMAL(10,2),
    refund_total_amount   DECIMAL(10,2),
    FOREIGN KEY (transaction_ID) REFERENCES Payment_Transaction(transaction_ID),
    FOREIGN KEY (categoryrefund_ID) REFERENCES Category_Refund(categoryrefund_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- RATING
-- ===============================
CREATE TABLE Rating (
    rating_ID             INT AUTO_INCREMENT PRIMARY KEY,
    rater_account_ID      INT,
    rating_type           ENUM('Tourist to Guide', 'Tourist To Tour Spots', 'Tourist to Tour Packages', 'Guide to Tourist') NOT NULL,
    rating_account_ID     INT,
    rating_tourpackage_ID INT,
    rating_tourspots_ID   INT,
    rating_value          DECIMAL(2,1) NOT NULL,
    rating_description    TEXT,
    rating_date           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rater_account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (rating_account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (rating_tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID),
    FOREIGN KEY (rating_tourspots_ID) REFERENCES Tour_Spots(spots_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================
-- REVIEW IMAGE
-- ===============================
CREATE TABLE Review_Image (
    review_ID        INT AUTO_INCREMENT PRIMARY KEY,
    rating_ID        INT,
    review_image_path VARCHAR(255) NOT NULL,
    review_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rating_ID) REFERENCES Rating(rating_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;







