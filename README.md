sudo mysql

LTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';

FLUSH PRIVILEGES;





create database zru_7917;



-- auto-generated definition



create table zru_7917_info

(

  last_name                varchar(45)                not null,

  first_name               varchar(45)                not null,

  middle_name              varchar(45) default 'NULL' null,

  birthday                 date                       null,

  actual_region_name       varchar(45)                not null,

  actual_region            int unsigned               not null,

  actual_city              varchar(45) default 'NULL' null,

  actual_street            varchar(45) default 'NULL' null,

  actual_house             varchar(45) default 'NULL' null,

  actual_corps             varchar(45) default 'NULL' null,

  actual_flat              varchar(45) default 'NULL' null,

  actual_postal            varchar(45) default 'NULL' null,

  registration_region_name varchar(45) default 'NULL' null,

  registration_region      int unsigned               not null,

  registration_city        varchar(45) default 'NULL' null,

  registration_street      varchar(45) default 'NULL' null,

  registration_house       varchar(45) default 'NULL' null,

  registration_corps       varchar(45) default 'NULL' null,

  registration_flat        varchar(45) default 'NULL' null,

  registration_postal      varchar(45) default 'NULL' null,

  applications_id          int unsigned               not null,

  customer_id              int unsigned               not null,

  constraint applications_id

    unique (applications_id)

);







-- auto-generated definition



create table zru_7917_data_fssp

(

  customer_id           int unsigned  null,

  debt                  decimal(8, 2) null,

  article               int           null,

  part                  int           null,

  paragraph             int           null,

  end_date              date          null,

  exe_production_number varchar(45)   null,

  exe_production_date   date          null,

  details               varchar(255)  null,

  subject               varchar(255)  null,

  department            varchar(255)  null,

  bailiff               varchar(255)  null

);





CREATE INDEX info_customer_id ON ISRU_21917_info(customer_id);

CREATE INDEX fssp_customer_id ON ISRU_21917_data_fssp(customer_id);







select 

       last_name,

       first_name,

       middle_name,

       birthday,

       subjects_actual.name,

       subjects_actual.code_const,

       actual_city,

       actual_street,

       actual_house,

       actual_corps,

       actual_flat,

       actual_postal,

       subjects.name,

       subjects.code_const,

       registred_city,

       registred_street,

       registred_house,

       registred_corps,

       registred_flat,

       registred_postal,

       #        CONCAT(IF(actual_street = "", "", actual_street), IF(actual_house = "", "", concat(", д. ", actual_house)),

       #               IF(actual_corps = "", "", concat("/", actual_corps)), IF(actual_flat = "", "", concat(", кв. ", actual_flat))) as al1,

       #        CONCAT(IF(actual_city = "", "", actual_city), IF(actual_postal = "", "", concat(", ", actual_postal)))                as al2,

       applications.id,

       customer_id

from applications

       join customers on customer_id = customers.id

       join subjects on registred_subject_id = subjects.id

       left join subjects as subjects_actual on actual_subject_id = subjects_actual.id

where applications.id in

      ();





{



    "name": "user/fssp",

    "authors": [

        {

            "name": "Rusanov Andrey",

            "email": "a.rusanov@robo.finance"

        }

    ],

    "require": {

        "guzzlehttp/guzzle": "~6.0",

        "vlucas/phpdotenv": "^2.5",

        "ext-json": "*",

        "monolog/monolog": "^1.24",

        "ext-pdo": "*",

      "ext-mbstring": "*"

    },

    "autoload": {

        "psr-4": {

            "services\\": "services/"

        },

        "files": ["services/requestToFssp.php"]

    }

}





LOAD DATA LOCAL INFILE 

'/home/andrey/Desktop/24988_.csv'



INTO TABLE ISRU_24988_info

CHARACTER SET utf8

FIELDS TERMINATED BY ','

ENCLOSED BY '"'

LINES TERMINATED BY '\n';

