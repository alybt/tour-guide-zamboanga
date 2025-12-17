
CREATE DATABASE tour;
USE tour;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE Country (
    country_ID         INT AUTO_INCREMENT PRIMARY KEY,
    country_name       VARCHAR(100) NOT NULL UNIQUE,
    country_codename   VARCHAR(10),
    country_codenumber VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Region (
    region_ID   INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(100) NOT NULL,
    country_ID  INT NOT NULL,
    FOREIGN KEY (country_ID) REFERENCES Country(country_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (region_name, country_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Province (
    province_ID   INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    region_ID     INT NOT NULL,
    FOREIGN KEY (region_ID) REFERENCES Region(region_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_province_per_region (province_name, region_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE City (
    city_ID      INT AUTO_INCREMENT PRIMARY KEY,
    city_name    VARCHAR(100) NOT NULL,
    province_ID  INT NOT NULL,
    FOREIGN KEY (province_ID) REFERENCES Province(province_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_city_per_province (city_name, province_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Barangay (
    barangay_ID   INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    city_ID       INT NOT NULL,
    FOREIGN KEY (city_ID) REFERENCES City(city_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_barangay_per_city (barangay_name, city_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Phone_Number (
    phone_ID     INT AUTO_INCREMENT PRIMARY KEY,
    country_ID   INT,
    phone_number VARCHAR(15) NOT NULL,
    FOREIGN KEY (country_ID) REFERENCES Country(country_ID),
    UNIQUE KEY unique_phone_per_country (country_ID, phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Contact_Info (
    contactinfo_ID         INT AUTO_INCREMENT PRIMARY KEY,
    phone_ID               INT,
    contactinfo_email      VARCHAR(100) NOT NULL,
    address_houseno        VARCHAR(50),
    address_street         VARCHAR(50),
    barangay_ID            INT,
    emergency_name         VARCHAR(225),
    emergency_relationship VARCHAR(225),
    emergency_phone_ID     INT,
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID),
    FOREIGN KEY (barangay_ID) REFERENCES Barangay(barangay_ID) ON DELETE CASCADE,
    FOREIGN KEY (emergency_phone_ID) REFERENCES Phone_Number(phone_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE User_Login (
    user_ID                   INT AUTO_INCREMENT PRIMARY KEY,
    name_first                VARCHAR(100),
    name_second               VARCHAR(225),
    name_middle               VARCHAR(225),
    name_last                 VARCHAR(225),
    name_suffix               VARCHAR(225),
    contactinfo_ID            INT,
    person_isPWD              TINYINT(4) NOT NULL DEFAULT 0,
    person_Nationality        VARCHAR(225),
    person_Gender             VARCHAR(225),
    person_DateOfBirth        DATE,
    user_username             VARCHAR(100) NOT NULL UNIQUE,
    user_password             VARCHAR(255) NOT NULL,
    user_last_password_change TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (contactinfo_ID) REFERENCES Contact_Info(contactinfo_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Role (
    role_ID   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Account_Info (
    account_ID           INT AUTO_INCREMENT PRIMARY KEY,
    user_ID              INT,
    role_ID              INT,
    account_status       ENUM('Active','Suspended','Pending') DEFAULT NULL,
    account_rating_score DECIMAL(3,2) DEFAULT 0.00,
    account_created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    account_profilepic   VARCHAR(500) DEFAULT NULL,
    account_nickname     VARCHAR(100) DEFAULT NULL,
    account_bio          VARCHAR(255) DEFAULT NULL,
    account_aboutme      TEXT DEFAULT NULL,
    is_deleted           DATETIME DEFAULT NULL,
    FOREIGN KEY (user_ID) REFERENCES User_Login(user_ID),
    FOREIGN KEY (role_ID) REFERENCES Role(role_ID),
    INDEX idx_nickname (account_nickname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Action (
    action_ID   INT AUTO_INCREMENT PRIMARY KEY,
    action_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Activity_Log (
    activity_ID          INT AUTO_INCREMENT PRIMARY KEY,
    account_ID           INT,
    action_ID            INT,
    activity_description TEXT,
    activity_timestamp   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Activity_View (
    activity_ID       INT NOT NULL,
    account_ID        INT NOT NULL,
    activity_isViewed TINYINT DEFAULT 0,
    PRIMARY KEY (account_ID, activity_ID),
    FOREIGN KEY (activity_ID) REFERENCES Activity_Log(activity_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Admin (
    admin_ID         INT AUTO_INCREMENT PRIMARY KEY,
    account_ID       INT,
    admin_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Guide_License (
    license_ID                  INT AUTO_INCREMENT PRIMARY KEY,
    license_number              VARCHAR(100) NOT NULL UNIQUE,
    license_created_date        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    license_issued_date         DATE,
    license_issued_by           VARCHAR(225),
    license_expiry_date         DATE,
    license_verification_status ENUM('Pending','Revoked','Verified','Active','Expired') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Guide (
    guide_ID      INT AUTO_INCREMENT PRIMARY KEY,
    account_ID    INT,
    license_ID    INT,
    guide_balance DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (license_ID) REFERENCES Guide_License(license_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Languages (
    languages_ID   INT AUTO_INCREMENT PRIMARY KEY,
    languages_name ENUM('English','Chavacano','Filipino') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Guide_Languages (
    guide_ID     INT NOT NULL,
    languages_ID INT NOT NULL,
    PRIMARY KEY (guide_ID, languages_ID),
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID) ON DELETE CASCADE,
    FOREIGN KEY (languages_ID) REFERENCES Languages(languages_ID) ON DELETE CASCADE
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

CREATE TABLE Tour_Spots (
    spots_ID          INT AUTO_INCREMENT PRIMARY KEY,
    spots_name        VARCHAR(225) NOT NULL,
    spots_category    VARCHAR(225) NOT NULL,
    spots_description TEXT,
    spots_address     VARCHAR(500) NOT NULL,
    spots_googlelink  VARCHAR(500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Tour_Spots_Images (
    spotsimage_ID   INT AUTO_INCREMENT PRIMARY KEY,
    spotsimage_PATH VARCHAR(500) NOT NULL,
    spots_ID        INT,
    FOREIGN KEY (spots_ID) REFERENCES Tour_Spots(spots_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Tour_Package (
    tourpackage_ID        INT AUTO_INCREMENT PRIMARY KEY,
    guide_ID              INT,
    tourpackage_name      VARCHAR(225) NOT NULL,
    tourpackage_desc      TEXT,
    schedule_days         INT NOT NULL DEFAULT 1,
    numberofpeople_maximum INT,
    numberofpeople_based  VARCHAR(50),
    pricing_currency      VARCHAR(10),
    pricing_foradult      DECIMAL(10,2),
    pricing_forchild      DECIMAL(10,2),
    pricing_foryoungadult DECIMAL(10,2),
    pricing_forsenior     DECIMAL(10,2),
    pricing_forpwd        DECIMAL(10,2),
    include_meal          TINYINT(1) DEFAULT 0,
    pricing_mealfee       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    transport_fee         DECIMAL(10,2) DEFAULT 0.00,
    pricing_discount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tourpackage_status    ENUM('Active','Inactive','Deleted','Rejected by the Admin','Suspended') NOT NULL DEFAULT 'Active',
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Tour_Package_Spots (
    packagespot_ID           INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_ID           INT NOT NULL,
    spots_ID                 INT,
    packagespot_activityname VARCHAR(255),
    packagespot_starttime    TIME,
    packagespot_endtime      TIME,
    packagespot_day          INT NOT NULL DEFAULT 1,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID) ON DELETE CASCADE,
    FOREIGN KEY (spots_ID) REFERENCES Tour_Spots(spots_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Request_Package (
    request_ID         INT AUTO_INCREMENT PRIMARY KEY,
    tourpackage_ID     INT,
    request_status     VARCHAR(50) NOT NULL,
    rejection_reason   TEXT,
    request_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Companion (
    companion_ID       INT AUTO_INCREMENT PRIMARY KEY,
    companion_name     VARCHAR(225) NOT NULL,
    companion_age      INT,
    companion_category ENUM('Infant','Child','Young Adult','Adult','Senior','PWD')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Booking (
    booking_ID             INT AUTO_INCREMENT PRIMARY KEY,
    tourist_ID             INT,
    booking_isselfincluded TINYINT(4) DEFAULT 0,
    booking_status         ENUM('Pending for Payment','Pending for Approval','Approved','In Progress','Completed','Cancelled','Cancelled - No Refund','Refunded','Failed','Rejected by the Guide','Booking Expired — Payment Not Completed','Booking Expired — Guide Did Not Confirm in Time') NOT NULL DEFAULT 'Pending for Payment',
    booking_created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tourpackage_ID         INT,
    booking_start_date     DATE NOT NULL,
    booking_end_date       DATE NOT NULL,
    booking_meeting_ID     INT DEFAULT NULL,
    booking_custom_meeting VARCHAR(255) DEFAULT NULL,
    itinerary_sent         TINYINT(1) DEFAULT 0,
    itinerary_sent_at      DATETIME,
    FOREIGN KEY (tourpackage_ID) REFERENCES Tour_Package(tourpackage_ID),
    FOREIGN KEY (tourist_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (booking_meeting_ID) REFERENCES Meeting_Point(meeting_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Booking_Bundle (
    bookingbundle_ID INT AUTO_INCREMENT PRIMARY KEY,
    booking_ID       INT,
    companion_ID     INT,
    FOREIGN KEY (booking_ID) REFERENCES Booking(booking_ID),
    FOREIGN KEY (companion_ID) REFERENCES Companion(companion_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Method_Category (
    methodcategory_ID             INT AUTO_INCREMENT PRIMARY KEY,
    methodcategory_name           VARCHAR(100) NOT NULL UNIQUE,
    methodcategory_type           VARCHAR(100) NOT NULL,
    methodcategory_processing_fee DECIMAL(10,2) NOT NULL,
    methodcategory_is_active      TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Method (
    method_ID         INT AUTO_INCREMENT PRIMARY KEY,
    methodcategory_ID INT,
    method_amount     DECIMAL(10,2),
    method_currency   VARCHAR(10),
    method_cardnumber VARCHAR(20),
    method_expmonth   VARCHAR(2),
    method_expyear    VARCHAR(4),
    method_cvc        VARCHAR(4),
    method_name       VARCHAR(100) NOT NULL,
    method_email      VARCHAR(100) NOT NULL,
    method_line1      VARCHAR(150),
    method_city       VARCHAR(100),
    method_postalcode VARCHAR(20),
    method_country    VARCHAR(10),
    method_status     ENUM('Active','Inactive') DEFAULT 'Active',
    method_created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    phone_ID          INT,
    FOREIGN KEY (methodcategory_ID) REFERENCES Method_Category(methodcategory_ID),
    FOREIGN KEY (phone_ID) REFERENCES Phone_Number(phone_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Payment_Transaction (
    transaction_ID           INT AUTO_INCREMENT PRIMARY KEY,
    paymongo_intent_id       VARCHAR(100),
    method_ID                INT,
    transaction_status       VARCHAR(50),
    transaction_reference    VARCHAR(100) UNIQUE,
    transaction_created_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    transaction_updated_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paymongo_refund_id       VARCHAR(100),
    booking_ID               INT,
    transaction_total_amount DECIMAL(10,2),
    FOREIGN KEY (method_ID) REFERENCES Method(method_ID),
    FOREIGN KEY (booking_ID) REFERENCES Booking(booking_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Guide_Earnings (
    earning_ID     INT AUTO_INCREMENT PRIMARY KEY,
    transaction_ID INT NOT NULL,
    platform_fee   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    earning_amount DECIMAL(10,2) NOT NULL,
    earning_status ENUM('Pending','On Hold','Released','Cancelled','Refunded') DEFAULT 'Pending',
    released_at    DATETIME NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_ID) REFERENCES Payment_Transaction(transaction_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Guide_Money_History (
    history_ID     INT AUTO_INCREMENT PRIMARY KEY,
    guide_ID       INT NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    balance_after  DECIMAL(10,2) NOT NULL,
    reference_ID   INT,
    reference_name ENUM('Earning','Refund','Payout','Adjustment') NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_ID) REFERENCES Guide(guide_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE CategoryRefund_Name (
    categoryrefundname_ID   INT AUTO_INCREMENT PRIMARY KEY,
    categoryrefundname_name VARCHAR(225)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Category_Refund (
    categoryrefund_ID     INT AUTO_INCREMENT PRIMARY KEY,
    categoryrefundname_ID INT,
    role_ID               INT,
    FOREIGN KEY (categoryrefundname_ID) REFERENCES CategoryRefund_Name(categoryrefundname_ID),
    FOREIGN KEY (role_ID) REFERENCES Role(role_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE Rating (
    rating_ID             INT AUTO_INCREMENT PRIMARY KEY,
    rater_account_ID      INT,
    rating_type           ENUM('Tourist to Guide','Tourist To Tour Spots','Tourist to Tour Packages','Guide to Tourist') NOT NULL,
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

CREATE TABLE Review_Image (
    review_ID         INT AUTO_INCREMENT PRIMARY KEY,
    rating_ID         INT,
    review_image_path VARCHAR(255) NOT NULL,
    review_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rating_ID) REFERENCES Rating(rating_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Conversation (
    conversation_ID  INT AUTO_INCREMENT PRIMARY KEY,
    user1_account_ID INT NOT NULL,
    user2_account_ID INT NOT NULL,
    last_message_ID  INT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_account_ID) REFERENCES Account_Info(account_ID),
    FOREIGN KEY (user2_account_ID) REFERENCES Account_Info(account_ID),
    UNIQUE KEY unique_conversation (user1_account_ID, user2_account_ID),
    INDEX idx_conversation_users (user1_account_ID, user2_account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE Message (
    message_ID        INT AUTO_INCREMENT PRIMARY KEY,
    conversation_ID   INT NOT NULL,
    sender_account_ID INT NOT NULL,
    message_content   TEXT NOT NULL,
    is_read           TINYINT(1) DEFAULT 0,
    sent_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_ID) REFERENCES Conversation(conversation_ID),
    FOREIGN KEY (sender_account_ID) REFERENCES Account_Info(account_ID),
    INDEX idx_message_conversation (conversation_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Guide Earnings View
CREATE OR REPLACE VIEW Guide_Earnings_View AS
SELECT 
    ge.earning_ID,
    g.guide_ID,
    ge.earning_amount,
    ge.platform_fee,
    (ge.earning_amount - ge.platform_fee) AS net_amount,
    ge.earning_status,
    ge.released_at
FROM Guide_Earnings ge
JOIN Payment_Transaction pt ON ge.transaction_ID = pt.transaction_ID
JOIN Booking b ON pt.booking_ID = b.booking_ID
JOIN Tour_Package tp ON b.tourpackage_ID = tp.tourpackage_ID
JOIN Guide g ON tp.guide_ID = g.guide_ID;

COMMIT;