<?php

trait AccountInfoTrait {

    public function getInfobyAccountID($account_ID){
        $sql = "SELECT 
            ni.name_first,
            ni.name_second,
            ni.name_middle,
            ni.name_last,
            ni.name_suffix,
 
            pn.phone_number,
            ci.contactinfo_email AS email,
 
            acc.account_aboutme,
            acc.account_bio,
            acc.account_nickname

        FROM Account_Info acc
 
        JOIN User_Login ul ON acc.user_ID = ul.user_ID
        JOIN Person pe ON ul.person_ID = pe.person_ID
        JOIN Name_Info ni ON pe.name_ID = ni.name_ID
 
        LEFT JOIN Contact_Info ci ON pe.contactinfo_ID = ci.contactinfo_ID
        LEFT JOIN Phone_Number pn ON ci.phone_ID = pn.phone_ID
 
        LEFT JOIN Address_Info ai ON ci.address_ID = ai.address_ID
        LEFT JOIN Barangay b ON ai.barangay_ID = b.barangay_ID
        LEFT JOIN City c ON b.city_ID = c.city_ID
        LEFT JOIN Province p ON c.province_ID = p.province_ID
        LEFT JOIN Region r ON p.region_ID = r.region_ID
        LEFT JOIN Country co ON r.country_ID = co.country_ID

        WHERE acc.account_ID = :account_ID"; 
        $db = $this->connect();
        $query = $db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC); 

    }


}





?>