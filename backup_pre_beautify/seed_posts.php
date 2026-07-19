<?php
// Include your database connection
require 'db.php';

echo "<h2>Seeding Dummy Posts...</h2>";

// First, we need to find at least one user in your database to be the "Author" of these posts.
$user_query = $conn->query("SELECT id, username FROM users WHERE role IN ('admin', 'superadmin', 'user') LIMIT 1");

if($user_query->num_rows == 0) {
    die("<p style='color:red;'>❌ Error: No users found in the database! Please run seed_users.php first or register an account.</p>");
}

$author = $user_query->fetch_assoc();
$author_id = $author['id'];
echo "<p>Assigning posts to author: <strong>@" . htmlspecialchars($author['username']) . "</strong></p><hr>";

// List of dummy articles with realistic placeholder image URLs
$dummy_posts = [
    [
        'title' => 'The Future of AI: What to Expect in 2025',
        'category' => 'Technology',
        'content' => 'Artificial intelligence is rapidly evolving. Experts predict that over the next few years, we will see significant advancements in natural language processing, automated coding, and autonomous systems. This will fundamentally change how we work and interact with machines.',
        'image_path' => 'https://picsum.photos/seed/tech/800/400',
        'status' => 'published'
    ],
    [
        'title' => 'Top 10 Hidden Gem Travel Destinations for the Summer',
        'category' => 'Travel',
        'content' => 'Tired of the usual tourist traps? We have compiled a list of breathtaking, off-the-beaten-path destinations that offer rich culture, stunning landscapes, and a fraction of the crowds. From the coastal villages of Eastern Europe to hidden valleys in South America...',
        'image_path' => 'https://picsum.photos/seed/travel/800/400',
        'status' => 'published'
    ],
    [
        'title' => 'Global Markets Rally Amid Tech Stock Surge',
        'category' => 'Finance',
        'content' => 'Stock markets around the world experienced a significant boost today, largely driven by a surge in major technology stocks. Investors are showing renewed confidence following better-than-expected quarterly earnings reports from industry giants.',
        'image_path' => 'https://picsum.photos/seed/finance/800/400',
        'status' => 'published'
    ],
    [
        'title' => '15-Minute Healthy Dinners for Busy Weeknights',
        'category' => 'Health & Lifestyle',
        'content' => 'Finding time to cook a nutritious meal after a long day can be tough. Try these five delicious, health-focused recipes that you can prepare, cook, and serve in under 15 minutes. Eating well doesn\'t have to be a chore!',
        'image_path' => 'https://picsum.photos/seed/food/800/400',
        'status' => 'published'
    ],
    [
        'title' => 'DRAFT: Exclusive Interview with the Championship MVP',
        'category' => 'Sports',
        'content' => 'We sat down with the star player immediately following the historic championship victory. They shared insights into their grueling training regimen, the team\'s mindset going into the final quarter, and what the future holds for their career.',
        'image_path' => 'https://picsum.photos/seed/sports/800/400',
        'status' => 'pending' // This one will show up in the Admin "Pending Approval" dashboard!
    ],
    [
        'title' => 'Review: The Latest Electric SUV Changing the Game',
        'category' => 'Automotive',
        'content' => 'We took the newest electric SUV on the market for a week-long test drive. With an impressive range, luxurious interior, and self-driving capabilities, it might just be the best EV released this decade.',
        'image_path' => 'https://picsum.photos/seed/car/800/400',
        'status' => 'published'
    ]
];

// Loop through each post and insert it into the database
foreach ($dummy_posts as $post) {
    $t = $conn->real_escape_string($post['title']);
    $c = $conn->real_escape_string($post['category']);
    $content = $conn->real_escape_string($post['content']);
    $img = $conn->real_escape_string($post['image_path']);
    $s = $conn->real_escape_string($post['status']);

    $sql = "INSERT INTO posts (user_id, title, category, content, image_path, status) 
            VALUES ($author_id, '$t', '$c', '$content', '$img', '$s')";
            
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>✅ Added post: <strong>$t</strong></p>";
    } else {
        echo "<p style='color:red;'>❌ Error adding post: " . $conn->error . "</p>";
    }
}

echo "<h3>🎉 All dummy posts added! Go check your Admin Dashboard or the Homepage.</h3>";
echo "<p style='color:red; font-weight:bold;'>IMPORTANT: Delete this file (seed_posts.php) from your folder now!</p>";
?>