# Icecat Extension

The open-source Icecat extension gives the capability to enrich Magento 2 product catalog from the open and free Icecat rich product catalog.

Installation Prerequisites: Magento Open Source 2.4.5


**Installation**

To install this extension, use the following commands:
- composer require icecat/magento2-extension
- php bin/magento module:enable Icecat_Datafeed
- php bin/magento setup:upgrade
- php bin/magento setup:static-content:deploy
- php bin/magento setup:di:compile
- php bin/magento indexer:reindex
- php bin/magento cache:flush

**Note(s):**

- After installation, verify if the **dragonmantank/cron-expression** library has been installed. You may check this by going into **/<yourproject>/vendor/dragonmantank**. If this exists then it is fine.If not, then a manual installation is recommended using the following command **composer require dragonmantank/cron-expression**

- Set the following command to the crontab - prerequisite for the scheduled job
 ```bash
* * * * * php /**<yourproject>**/bin/magento icecat:queue-runner 2>&1 | grep -v "Icecat Cron" >> /**<yourproject>**/var/log/icecat.cron.log
```

- To be able to play/embed YouTube videos, the YouTube API configuration is required in the Magento 2 as mentioned in the following link- https://docs.magento.com/user-guide/v2.3/catalog/product-video.html 

- The system.log file is logged with the details when the product is not assigned to any of the store(s). Sample log
![img1](./doc/images/image14.png)

- The Icecat Extension will be added in the default Magento store configuration section.
![img1](./doc/images/image16.png)

**Features**
- An Open-Source plug-and-play extension and MIT licensed under Icecat NV.
- A user-friendly interface to configure the Icecat import attributes.
- Multi-store and multi-website catalog management to drive more business.
- Import all the product data from Open-Icecat catalog including Icecat categories, marketing text, specifications, images, YouTube videos, pdf, product stories, reasons to buy, bullet points and product reviews.
- Recurring bulk imports, Automatic -full- or Manual -Delta & Full- or single product imports.
- Complete downloadable import job summary statistics for immediate action.

**MIT license And Contact Details**

![img1](./doc/images/image19.png)

**Configuration Details**
- General Configuration - Enable the Icecat Extension.
![img1](./doc/images/image15.png)

- User Authentication - An Open-Icecat user account and API access token is required to import the product data via Icecat API. New users could easily get these details by using the “Register with Icecat” option as mentioned below. 
![img1](./doc/images/image8.png)

- The API access token could be easily generated by logging-in on icecat.biz.
![img1](./doc/images/image6.png)

- Store Configuration - The extension will import the product data only in the selected stores locales.
![img1](./doc/images/image26.png)

- Icecat Configuration - One time attribute mapping is required to import the data via Icecat API. These attributes are created by the end user in the Magento environment and the icecat extension will use these attributes to match the product in the Icecat catalog.

    Notes:
    - The attributes scope must be global and of type text.
    - The attributes must be part of the attribute set used for the magento product catalog.
![img1](./doc/images/image29.png)

- Icecat Categorization - Once enabled, a one time new root category named “Icecat Categories” will be created without any impact on the Default Category. The Icecat Categories then could be utilized as per need, for example - by assigning to the required store into General Configurations or by moving specific categories to the Default Category.
![img1](./doc/images/image15.png)

- Icecat Media - Images and YouTube videos -only public- are imported into the Magento’s default “Images And Videos'' section. If more than one store is configured, the user will see all the images and videos in the Default Store View.  However, once a particular store view is enabled, images and videos will be active for that store view and all the images and videos from other stores views will be marked as hidden. 

    All the documents are stored in the table icecat_product_attachtment.
![img1](./doc/images/image30.png)

- Icecat Specifications - All the specifications are imported into the icecat_specifications attribute with default Icecat heading color. The user has flexibility to change the color in order to match their Web shop’s theme.
![img1](./doc/images/image18.png)

- Icecat Other Enriched Fields - Icecat Product Reviews are stored in the table icecat_product_reviews. All the remaining fields are Magento attributes and have been created by default during the Icecat extension installation.

    ![img1](./doc/images/image23.png)

- Icecat Basic Fields - By default all the basic Icecat attributes will be created and mapped in order to save the time. Only the standard Magento attributes are required to be mapped by the Magento user. The user has flexibility to keep only the required fields as per need.

    ![img1](./doc/images/image11.png)

- Recurring bulk imports - Users will be able to automate full import with the cron expression.
    
    Full Import takes all the products from the Magento product catalog and for the matched products in the Icecat catalog, imports back the specified product data into the Magento catalog as per the configurations.
    
    Delta Import takes all the products which have been updated in the Magento product catalog since the last full/automatic import run.

    ![img1](./doc/images/image28.png)

- Single Product Import - On the product details page, the user has flexibility to import single product data on    demand.

    ![img1](./doc/images/image2.png)

**Icecat Categories**

- Icecat Categories - On very first import, automatically a new root category named “Icecat Categories” will be created.

    ![img1](./doc/images/image24.png)

**Product Data Details**
- On the product details page, all the Icecat product data will be available under the highlighted sections.
    ![img1](./doc/images/image10.png)

- Content
    ![img1](./doc/images/image12.png)

    ![img1](./doc/images/image1.png)

- Icecat Product Reviews

    ![img1](./doc/images/image3.png)

- Icecat Product Attachments

    ![img1](./doc/images/image9.png)

- Icecat Product Content

    ![img1](./doc/images/image21.png)

    ![img1](./doc/images/image7.png)

    ![img1](./doc/images/image5.png)

    ![img1](./doc/images/image4.png)

- Bullet Points

    ![img1](./doc/images/image22.png)

- Images And Videos

    ![img1](./doc/images/image13.png)

YouTube Video View

![img1](./doc/images/image25.png)

**Related Products**
While importing the product from Icecat Catalog, all the related products will be searched in the Magento Catalog using Brand Name and Product Code. All the matched products will be then linked under the main product.

**Import Statistics**

![img1](./doc/images/image17.png)

Not found in Icecat: These are the products present in the Magento Catalog but not in the Icecat Catalog. If you would like to publish these products into Icecat, contact us.
 
Forbidden in Icecat: A full icecat subscription is required to access these products.

![img1](./doc/images/image27.png)
