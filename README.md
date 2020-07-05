# Malaysia E-wallet

## Overview
+ This code basically skeleton structure of major ewallet in Malaysia. Other type of ewallet might be different in term of hashing and encryption. but the flow and structure mostly same.

## Functionalities
+ Payment - where the financial transaction occur.
+ Inquiry - where the PG need to know the latest status in case there are any delayed or internal problem form ewallet provider.
+ Refund - this function normally used when the transaction aged more than 24 hours. because normally there will extra charges when using this function
+ Reversal/void - this function normally used for transaction aged between 0-24 hours. 

## Techonology / Knowledge
+ PHP 5.6/7.+
+ SQL
+ Hashing
