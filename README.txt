#######################################  
#### WCMP IPAY MobileMoney Gateway ####
#######################################

== Requirements ====
1) Ensure Woocommerece and WC Marketplace(Wcmp) is installed first.

== Installation Process ===
1) The plugin is didtributed as a zip file.
2) Upload the zip file into wordpress, Install and Activate.
3) The plugin contains both the iPay Woocommerce Checkout Gateway and WCMP gateway.  


== WCMP Ipay Setup ===
1) Navigate to WCMP admin dashbaord.
2) Click On Payment tab and choose iPay Mobilemoney (Under How/When to Pay)
3) Enter you vendor Id and Secrete Key and enable split payment
4) Save the changes.


== Enable Vendor Registration ==
1) Navigate to Wordpress setttings and enbale Membership.
2) Set new user role as Vendor.
3) Back in the WCMP Settings, in the Vendor Settings Tab, you can add more field to capture more information about vendor during registration.
4) Navigate to Woocommerce settings. Under the Account and Privacy, enable the first four checkboxes to enable woocommerce to allow vendors to self register.

== Vendor Setup ===
1) When WCMP plugin is installed, the two new links(destination)  are added to your website i.e. Vendor Dashbaord and Vendor Registration. If a vendor registration is approved, Vendor will be able to access the dashboaurd.
2) Navigate to Store Settings  on the Vendor dashboard. Here a vendors can choose the method they want to receive payments by (These are the methods that have enabled in the WCMP admin dashbaord).
3) For iPay Mobile money, vendor provides phone number and the channel(mpesa, airtelmoney or elipa).
4) iPay mobilemoney is an auto payment mode thus, the funds will be disbursed at the set interval.



==== PENDING ISSUES =====
1) The Order status on the Vendor dashboard are still not updating to reflect stages of payment. 
2) There are Order information that are not being stored or retrieved properly.
3) iPay Pesalink is still under development





