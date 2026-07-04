<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Featured
            ['Beats Solo Wireless Headphone','featured',299.00,249.00,17,'Headphone.png','Headphone.avif','Headphone2.avif','Headphone3.avif'],
            ['Wireless Gaming Controller','featured',80.00,65.00,19,'Controller.png','Controller2.webp','Controller3.webp','Controller4.webp'],
            ['AirPods Pro Gen 2','featured',249.00,175.00,30,'airpods.png','ipad.png','ipadp2.webp','ipadp3.webp'],
            ['Gaming Mouse Pro','featured',59.00,45.00,24,'mouse.png','mouse.png','mouse.png','mouse.png'],
            ['27-inch 4K Monitor','featured',499.00,399.00,20,'monitor.png','monitor.png','monitor.png','monitor.png'],
            ['Mechanical Keyboard RGB','featured',129.00,99.00,23,'keyboard.png','keyboard.png','keyboard.png','keyboard.png'],
            // Apples
            ['iPhone 17 Pro Max','apples',1199.00,1099.00,8,'ip17.png','ip17pm2.jpg','ip17pm3.jpg','ip17pm4.jpg'],
            ['iPhone 17','apples',899.00,849.00,6,'iphone17.png','ip17pm2.jpg','ip17pm3.jpg','ip17pm4.avif'],
            ['MacBook Air M3','apples',1299.00,1199.00,8,'MacBook.png','macbook_pro.png','macbook_pro.png','macbook_pro.png'],
            ['MacBook Pro 16"','apples',2499.00,2299.00,8,'macbook_pro.png','MacBook.png','macbook_pro.png','macbook_pro.png'],
            ['Apple Watch Series 10','apples',399.00,349.00,13,'watch.png','ice_watch.png','ice_watch.png','ice_watch.png'],
            ['iPad Pro 12.9"','apples',1099.00,999.00,9,'ipad_screen.png','ipad.png','ipadp2.jpg','ipad4.jpg'],
            // Samsungs
            ['Samsung Galaxy A56','samsungs',499.00,449.00,10,'A56.png','A56.png','A56.png','A56.png'],
            ['Samsung Galaxy Book4','samsungs',899.00,799.00,11,'GalaxyBook.png','GalaxyBook.png','GalaxyBook.png','GalaxyBook.png'],
            ['Samsung Galaxy Tab S6','samsungs',649.00,549.00,15,'TabS6.png','TabS6.png','TabS6.png','TabS6.png'],
            ['Samsung Smart Watch','samsungs',299.00,249.00,17,'samsung_watch.png','samsung_watch.png','samsung_watch.png','samsung_watch.png'],
            ['Samsung Neo TV','samsungs',1299.00,1099.00,15,'Neo.png','Neo.png','Neo.png','Neo.png'],
            ['Samsung Refrigerator','samsungs',1899.00,1699.00,11,'Refrigerator.png','Refrigerator.png','Refrigerator.png','Refrigerator.png'],
            // Sony
            ['Sony PlayStation 5 Console','sony',499.00,449.00,10,'PS5.png','ps5 console2.webp','ps5 console3.webp','ps5 console4.webp'],
            ['Sony PS5 DualSense Controller','sony',79.00,69.00,13,'Controller.png','Controller2.webp','Controller3.webp','Controller4.webp'],
            ['Sony Bravia XR TV','sony',1499.00,1299.00,13,'Sony_TV.png','TV.png','TV.png','TV.png'],
            ['Sony WH-1000XM5 Headphones','sony',349.00,299.00,14,'Sony_Headphone.png','Headphone.png','Headphone.avif','Headphone2.avif'],
            ['Sony Alpha Camera','sony',1999.00,1799.00,10,'Sony_Camera.png','camera.png','camera.png','camera.png'],
            ['Sony PlayStation 2 Classic','sony',199.00,149.00,25,'PS2.png','PS5.png','PS5.png','PS5.png'],
            // Panasonics
            ['Panasonic Inverter Refrigerator','panasonics',1599.00,1399.00,13,'Panasonic_Refri.png','Refrigerator.png','Refrigerator.png','Refrigerator.png'],
            ['Panasonic Air Conditioner','panasonics',899.00,799.00,11,'AC.png','AC.png','AC.png','AC.png'],
            ['Panasonic Hair Dryer','panasonics',129.00,99.00,23,'Dryer.png','Dryer.png','Dryer.png','Dryer.png'],
            ['Panasonic Audio Speaker','panasonics',199.00,169.00,15,'Audio.png','speaker.png','speaker2.png','speaker3.png'],
            ['Panasonic Lumix Camera','panasonics',899.00,799.00,11,'camera.png','camera.png','camera.png','camera.png'],
            ['Panasonic Home Speaker','panasonics',249.00,199.00,20,'speaker4.png','speaker.png','speaker2.png','speaker3.png'],
        ];

        foreach ($products as $p) {
            Product::create([
                'product_name' => $p[0],
                'product_category' => $p[1],
                'product_price' => $p[2],
                'product_discount' => $p[3],
                'product_special_offer' => $p[4],
                'product_image' => $p[5],
                'product_image2' => $p[6],
                'product_image3' => $p[7],
                'product_image4' => $p[8],
                'created_at' => now(),
            ]);
        }
    }
}
