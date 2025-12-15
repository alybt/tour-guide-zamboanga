Table Country {
  country_ID int [pk, increment]
  country_name varchar(100) [unique, not null]
  country_codename varchar(10)
  country_codenumber varchar(10)
}

Table Phone_Number {
  phone_ID int [pk, increment]
  country_ID int
  phone_number varchar(15) [not null]

  indexes {
    (country_ID, phone_number) [unique]
  }
}

Table Region {
  region_ID int [pk, increment]
  region_name varchar(100) [not null]
  country_ID int

  indexes {
    (region_name, country_ID) [unique]
  }
}

Table Province {
  province_ID int [pk, increment]
  province_name varchar(100) [not null]
  region_ID int

  indexes {
    (province_name, region_ID) [unique]
  }
}

Table City {
  city_ID int [pk, increment]
  city_name varchar(100) [not null]
  province_ID int

  indexes {
    (city_name, province_ID) [unique]
  }
}

Table Barangay {
  barangay_ID int [pk, increment]
  barangay_name varchar(100) [not null]
  city_ID int

  indexes {
    (barangay_name, city_ID) [unique]
  }
}

Table Address_Info {
  address_ID int [pk, increment]
  address_houseno varchar(50) [not null]
  address_street varchar(50) [not null]
  barangay_ID int

  indexes {
    (address_houseno, address_street, barangay_ID) [unique]
  }
}

Table Emergency_Info {
  emergency_ID int [pk, increment]
  emergency_Name varchar(225) [not null]
  emergency_Relationship varchar(225) [not null]
  phone_ID int
}

Table Contact_Info {
  contactinfo_ID int [pk, increment]
  address_ID int
  phone_ID int
  contactinfo_email varchar(100) [not null]
  emergency_ID int
}

Table Name_Info {
  name_ID int [pk, increment]
  name_first varchar(100) [not null]
  name_second varchar(225)
  name_middle varchar(225)
  name_last varchar(225) [not null]
  name_suffix varchar(225)
}

Table Person {
  person_ID int [pk, increment]
  name_ID int
  contactinfo_ID int
  person_Nationality varchar(225)
  person_Gender varchar(225)
  person_DateOfBirth date
}

Table User_Login {
  user_ID int [pk, increment]
  person_ID int
  user_username varchar(100) [not null, unique]
  user_password varchar(255) [not null]
}

Table Role {
  role_ID int [pk, increment]
  role_name varchar(100) [not null, unique]
}

Table Account_Info {
  account_ID int [pk, increment]
  user_ID int
  role_ID int
  account_status enum('Active', 'Suspended', 'Pending')
  account_rating_score decimal(3,2) [default: 0.00]
  account_created_at timestamp 
}

Table Action {
  action_ID int [pk, increment]
  action_name varchar(100) [not null, unique]
}

Table Activity_Log {
  activity_ID int [pk, increment]
  account_ID int
  action_ID int
  activity_description text
  activity_timestamp timestamp 
}

Table Admin {
  admin_ID int [pk, increment]
  account_ID int
  admin_created_at timestamp 
}

Table Guide_License {
  lisence_ID int [pk, increment]
  lisence_number varchar(100) [not null, unique]
  lisence_created_date date [not null]
  lisence_issued_date date [not null]
  lisence_issued_by varchar(225) [not null]
  lisence_expiry_date date [not null]
  lisence_verification_status varchar(50) [not null]
  lisence_status varchar(50) [not null]
}

Table Languages {
  languages_ID int [pk, increment]
  language_name enum('English', 'Chavacano', 'Filipino') [not null, unique]
}

Table Guide {
  guide_ID int [pk, increment]
  account_ID int
  lisence_ID int
}

Table Guide_Languages {
  guide_ID int
  languages_ID int

  indexes {
    (guide_ID, languages_ID) [pk]
  }
}

Table Pricing {
  pricing_ID int [pk, increment]
  pricing_currency varchar(10) [not null]
  pricing_foradult decimal(10,2) [not null]
  pricing_forchild decimal(10,2)
  pricing_foryoungadult decimal(10,2)
  pricing_forsenior decimal(10,2)
  pricing_forpwd decimal(10,2)
  include_meal boolean [default: false]
  pricing_mealfee decimal(10,2) [default: 0.00]
  transport_fee decimal(10,2) [default: 0.00]
  pricing_discount decimal(10,2) [not null]
}

Table Number_Of_People {
  numberofpeople_ID int [pk, increment]
  pricing_ID int
  numberofpeople_maximum int [not null]
  numberofpeople_based varchar(50) [not null]
}

Table Schedule {
  schedule_ID int [pk, increment]
  numberofpeople_ID int
  schedule_days int [default: 1, not null]
}

Table Tour_Spots {
  spots_ID int [pk, increment]
  spots_name varchar(225) [not null]
  spots_category varchar(225) [not null]
  spots_description text
  spots_address varchar(500) [not null]
  spots_googlelink varchar(500)
}

TABLE Tour_Spots_Images(
    spotsimage_ID int [pk, increment]
    spotsimage_PATH varchar(225) [not null]
    spots_ID int
);

Table Tour_Package {
  tourpackage_ID int [pk, increment]
  tourpackage_name varchar(225) [not null]
  tourpackage_desc text
  guide_ID int
  schedule_ID int
}

Table Tour_Package_Spots {
  packagespot_ID int [pk, increment]
  tourpackage_ID int
  spots_ID int
  packagespot_activityname varchar(255)
  packagespot_starttime time
  packagespot_endtime time
  packagespot_day int [not null]
}

Table Request_Package {
  request_ID int [pk, increment]
  tourpackage_ID int
  request_status varchar(50) [not null]
  rejection_reason text
  request_created_at timestamp 
  request_updated_at timestamp 
}

Table Booking {
  booking_ID int [pk, increment]
  tourist_ID int
  booking_status enum('Pending for Payment', 'Pending for Approval', 'Approved', 'In Progress', 'Completed', 'Cancelled', 'Refunded', 'Failed') [default: 'Pending for Approval', not null]
  booking_created_at timestamp 
  tourpackage_ID int
  booking_start_date date [not null]
  booking_end_date date [not null]
}

Table Companion_Category {
  companion_category_ID int [pk, increment]
  companion_category_name enum('Adult', 'Children', 'Senior', 'PWD') [not null, unique]
}

Table Companion {
  companion_ID int [pk, increment]
  companion_name varchar(225) [not null]
  companion_age int [not null]
  companion_category_ID int
}

Table Booking_Bundle {
  bookingbundle_ID int [pk, increment]
  booking_ID int
  companion_ID int
}

Table Method_Category {
  methodcategory_ID int [pk, increment]
  methodcategory_name varchar(100) [not null, unique]
  methodcategory_type varchar(100) [not null]
  methodcategory_processing_fee decimal(10,2) [not null]
  methodcategory_is_active boolean [default: true]
}

Table Method {
  method_ID int [pk, increment]
  methodcategory_ID int
  method_amount decimal(10,2)
  method_currency varchar(10)
  method_cardnumber varchar(20)
  method_expmonth varchar(2)
  method_expyear varchar(4)
  method_cvc varchar(4)
  method_name varchar(100) [not null]
  method_email varchar(100) [not null]
  method_line1 varchar(150)
  method_city varchar(100)
  method_postalcode varchar(20)
  method_country varchar(10)
  method_status enum('Active', 'Inactive') [default: 'Active']
  method_created_at datetime 
  phone_ID int
}

Table Payment_Transaction {
  transaction_ID int [pk, increment]
  booking_ID int [not null]
  method_ID int
  transaction_total_amount decimal(10,2) [not null]
  transaction_status varchar(50)
  transaction_reference varchar(100) [unique]
  transaction_created_date timestamp 
  transaction_updated_date timestamp 
  paymongo_intent_id varchar(100)
  paymongo_refund_id varchar(100)
}

Table CategoryRefund_Name {
  categoryrefundname_ID int [pk, increment]
  categoryrefundname_name varchar(225)
}

Table Category_Refund {
  categoryrefund_ID int [pk, increment]
  categoryrefundname_ID int
  role_ID int
}

Table Refund {
  refund_ID int [pk, increment]
  transaction_ID int
  categoryrefund_ID int
  refund_reason text
  refund_status varchar(50) [not null]
  refund_requested_date timestamp 
  refund_approval_date timestamp 
  refund_processingfee decimal(10,2)
  refund_refundfee decimal(10,2)
  refund_total_amount decimal(10,2)
}

Table Rating {
  rating_ID int [pk, increment]
  rater_account_ID int
  rating_type enum('Tourist', 'Guide', 'Tour Spots', 'Tour Package') [not null]
  rating_account_ID int
  rating_tourpackage_ID int
  rating_tourspots_ID int
  rating_value decimal(2,1) [not null]
  rating_description text
  rating_date timestamp 
}

Table Review_Image {
  review_ID int [pk, increment]
  rating_ID int
  review_image_path varchar(255) [not null]
  review_created_at timestamp 
}


Ref: Country.country_ID < Phone_Number.country_ID
Ref: Country.country_ID < Region.country_ID
Ref: Region.region_ID < Province.region_ID
Ref: Province.province_ID < City.province_ID
Ref: City.city_ID < Barangay.city_ID
Ref: Barangay.barangay_ID < Address_Info.barangay_ID


Ref: Phone_Number.phone_ID < Emergency_Info.phone_ID
Ref: Address_Info.address_ID < Contact_Info.address_ID
Ref: Phone_Number.phone_ID < Contact_Info.phone_ID
Ref: Emergency_Info.emergency_ID - Contact_Info.emergency_ID
Ref: Name_Info.name_ID - Person.name_ID
Ref: Contact_Info.contactinfo_ID - Person.contactinfo_ID
Ref: Person.person_ID - User_Login.person_ID
Ref: User_Login.user_ID - Account_Info.user_ID
Ref: Role.role_ID < Account_Info.role_ID
Ref: Account_Info.account_ID < Activity_Log.account_ID
Ref: Action.action_ID < Activity_Log.action_ID
Ref: Account_Info.account_ID - Admin.account_ID
Ref: Account_Info.account_ID - Guide.account_ID
Ref: Guide_License.lisence_ID - Guide.lisence_ID


Ref: Guide.guide_ID <> Guide_Languages.guide_ID
Ref: Languages.languages_ID < Guide_Languages.languages_ID


Ref: Pricing.pricing_ID < Number_Of_People.pricing_ID
Ref: Number_Of_People.numberofpeople_ID < Schedule.numberofpeople_ID
Ref: Guide.guide_ID < Tour_Package.guide_ID
Ref: Schedule.schedule_ID - Tour_Package.schedule_ID
Ref: Tour_Package.tourpackage_ID <> Tour_Package_Spots.tourpackage_ID
Ref: Tour_Spots.spots_ID <> Tour_Package_Spots.spots_ID
Ref: Tour_Package.tourpackage_ID < Request_Package.tourpackage_ID
Ref: Tour_Spots_Images.spots_ID < Tour_Spots.spots_ID

Ref: Account_Info.account_ID < Booking.tourist_ID
Ref: Tour_Package.tourpackage_ID < Booking.tourpackage_ID
Ref: Companion_Category.companion_category_ID < Companion.companion_category_ID
Ref: Booking.booking_ID <> Booking_Bundle.booking_ID
Ref: Companion.companion_ID <> Booking_Bundle.companion_ID
Ref: Method_Category.methodcategory_ID < Method.methodcategory_ID
Ref: Phone_Number.phone_ID < Method.phone_ID
Ref: Booking.booking_ID - Payment_Transaction.booking_ID
Ref: Method.method_ID - Payment_Transaction.method_ID


Ref: CategoryRefund_Name.categoryrefundname_ID < Category_Refund.categoryrefundname_ID
Ref: Role.role_ID < Category_Refund.role_ID
Ref: Payment_Transaction.transaction_ID - Refund.transaction_ID
Ref: Category_Refund.categoryrefund_ID < Refund.categoryrefund_ID


Ref: Account_Info.account_ID < Rating.rater_account_ID
Ref: Account_Info.account_ID < Rating.rating_account_ID
Ref: Tour_Package.tourpackage_ID < Rating.rating_tourpackage_ID
Ref: Tour_Spots.spots_ID < Rating.rating_tourspots_ID
Ref: Rating.rating_ID < Review_Image.rating_ID
