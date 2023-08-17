<?php

namespace App;

class Constants {

   //   STATUS
      const ACTIVE = 1;
      const NOT_ACTIVE = 2;

   // Order Details   seates types
      const  PARTIALLY = 0;
      const FULL_PLEACE = 1;

   // Order    type_ids
   const ORDERED = 3;
   const ON_THE_WAY = 4;
   const COMPLETED = 5;

   // Offer    type_ids
   const NEW = 6;
   const ACCEPT = 7;
   const CANCEL = 8;

   // Offer    create_type  and cancel_type
   const  ORDER_DETAIL= 0;
   const ORDER = 1;


}