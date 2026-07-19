<?php
/* reseed_articles.php  —  DEV ONLY (delete before final submission)
 * Replaces the old Lorem-ipsum/dummy posts with 45 realistic articles across 12
 * categories, using local offline-safe cover images (assets/img/<cat>-a|b.jpg),
 * varied authors, and dates spread over the last ~90 days. Adds a few sample
 * comments too. Run from the command line:  php assets/dev/reseed_articles.php
 */
$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error . "\n"); }
$conn->set_charset("utf8mb4");

/* ---- 1. Clean slate: remove all existing posts (comments cascade via FK) ---- */
$conn->query("DELETE FROM comments");
$conn->query("DELETE FROM posts");
echo "Cleared old posts and comments.\n";

/* ---- 2. Authors to spread articles across (existing user ids) ---- */
$author_ids = [];
$r = $conn->query("SELECT id FROM users ORDER BY id");
while ($row = $r->fetch_assoc()) $author_ids[] = (int)$row['id'];
if (!$author_ids) { die("No users found — run seed_users first.\n"); }

/* ---- 3. The article dataset: [title, category, body, status?] ---- */
$P = 'published'; $Q = 'pending';
$articles = [
  // Technology
  ['AI Assistants Are Quietly Rewriting How We Work','Technology',"Artificial intelligence has moved from novelty to daily tool faster than almost anyone predicted. From drafting emails to summarising long reports, AI assistants now sit inside the software millions of people already use.\n\nExperts say the real shift is not replacement but augmentation: workers who learn to delegate routine tasks to AI are finding more time for creative and strategic work. The challenge ahead is trust, accuracy, and knowing when a human should stay in the loop.",$P],
  ['The Race to Build Faster, Cooler Data Centres','Technology',"Behind every app and website sits a data centre, and the demand for computing power has never been higher. Operators are now competing not just on speed but on how efficiently they can cool thousands of humming servers.\n\nNew designs use outside air, liquid cooling, and even seawater to cut energy use. The payoff is lower costs and a smaller carbon footprint for an industry that quietly consumes a growing slice of the world's electricity.",$P],
  ['Why Open-Source Software Runs the Modern Internet','Technology',"Much of the technology we rely on every day is built on open-source software, code that anyone can inspect, use, and improve. It powers web servers, phones, and the cloud services behind countless businesses.\n\nSupporters argue that openness leads to more secure and reliable software because thousands of eyes can spot problems. The model also lets small teams build on the work of a global community rather than starting from scratch.",$P],
  ['5G Is Finally Living Up to Its Promise','Technology',"After years of hype, 5G networks are beginning to deliver the speed and responsiveness that early adverts promised. Coverage has expanded, and devices have caught up.\n\nThe biggest gains are showing up in unexpected places: smarter factories, remote healthcare, and live events streamed in high definition. As prices fall, the technology is quietly becoming part of the everyday background.",$Q],

  // Business
  ['Small Businesses Bet Big on Local-First Commerce','Business',"A growing number of small businesses are leaning into their local roots rather than trying to compete with global giants. Community-focused shops are using social media and same-day delivery to win loyal customers nearby.\n\nAnalysts say the local-first approach can be surprisingly resilient. Shorter supply lines, personal service, and word-of-mouth create advantages that even the largest retailers struggle to copy.",$P],
  ['Remote Work Reshapes the Office Real-Estate Market','Business',"The shift to hybrid and remote work has left a lasting mark on commercial property. Companies are trading large headquarters for smaller, flexible spaces designed for collaboration rather than daily desks.\n\nLandlords are responding by turning empty floors into shared offices, studios, and even housing. The result is a slow but significant reinvention of how cities use their downtown cores.",$P],
  ['How Subscription Models Took Over Every Industry','Business',"From software to razors to coffee, the subscription model has spread into nearly every corner of the economy. Predictable monthly revenue has proven irresistible to businesses of all sizes.\n\nFor customers, the appeal is convenience, but fatigue is setting in as bills add up. The companies that thrive are those that keep proving their value month after month.",$P],
  ['Supply Chains Are Getting Shorter and Smarter','Business',"Recent disruptions taught companies a hard lesson about relying on distant, single-source suppliers. Many are now bringing production closer to home and building in backup options.\n\nTechnology is helping, with real-time tracking and forecasting that flags problems before they spread. The trade-off is cost, but resilience has become a price many businesses are willing to pay.",$P],

  // Finance
  ['Central Banks Signal a Cautious Path on Rates','Finance',"Policymakers around the world are treading carefully as they weigh inflation against the risk of slowing growth. Recent statements suggest a preference for small, measured moves rather than dramatic swings.\n\nMarkets have responded with cautious optimism. For households, the message is that borrowing costs may stay elevated for a while yet, making budgeting and saving all the more important.",$P],
  ['The Quiet Rise of Digital-Only Banking','Finance',"Banks without a single branch are winning over customers who value speed and simplicity. Opening an account takes minutes, and everything happens through an app.\n\nTraditional banks are racing to catch up, investing heavily in their own digital tools. The competition is good news for customers, who now expect instant transfers and clear, jargon-free service.",$P],
  ['What Rising Bond Yields Mean for Everyday Savers','Finance',"Bond yields rarely make headlines, yet they quietly shape the returns on savings, pensions, and mortgages. When yields rise, the effects ripple through the whole economy.\n\nFor savers, higher yields can mean better returns on safe investments for the first time in years. Advisers suggest reviewing where your money sits to make sure it is working as hard as it can.",$P],
  ['Personal Finance Apps Are Changing How Gen Z Saves','Finance',"A new generation is learning about money through apps that turn budgeting into something closer to a game. Automatic round-ups, spending insights, and gentle nudges make saving feel effortless.\n\nCritics warn that not every app has the user's best interest at heart, but the overall trend is positive: young people are engaging with their finances earlier and more confidently than before.",$P],

  // Health
  ['Sleep Science: Why Seven Hours Is the New Gold Standard','Health',"Researchers continue to find that consistent, quality sleep is one of the most powerful things we can do for our health. Seven to nine hours a night is linked to better mood, memory, and immunity.\n\nThe advice is refreshingly simple: keep a regular schedule, dim the lights before bed, and put the phone away. Small habits, repeated nightly, add up to a big difference.",$P],
  ['Walking Meetings and the Return of Movement at Work','Health',"Sitting for hours has been called the new smoking, and workplaces are taking note. Walking meetings, standing desks, and short movement breaks are becoming part of the routine.\n\nThe benefits go beyond physical health. Many people find they think more clearly and feel more creative after a short walk, turning a health tip into a productivity boost.",$P],
  ['The Mental-Health Benefits of Getting Outside','Health',"Spending time in nature has a measurable effect on stress and mood, even in short doses. A few minutes among trees or by water can lower the body's stress signals.\n\nDoctors in some countries now prescribe time outdoors alongside traditional treatments. You do not need a wilderness, either; a local park or garden offers much of the same benefit.",$P],
  ['Nutrition Myths That Refuse to Die','Health',"Despite decades of research, plenty of food myths still shape how people eat. From fears of a single ingredient to miracle diets, misinformation spreads easily.\n\nNutritionists stress balance over extremes: plenty of vegetables, whole foods, and moderation. The most sustainable diet, they say, is one you can actually stick to.",$Q],

  // Science
  ['Astronomers Map the Most Detailed View of the Galaxy Yet','Science',"A new survey has produced the most detailed map of our galaxy to date, charting the positions and motions of billions of stars. The data is helping scientists understand how the Milky Way formed.\n\nBeyond the striking images, the map is a tool. Researchers use it to trace hidden structures and to search for clues about the mysterious dark matter that holds galaxies together.",$P],
  ['Breakthrough Battery Chemistry Could Outlast Lithium','Science',"Scientists are testing new battery chemistries that promise longer life, faster charging, and cheaper materials than today's lithium cells. Sodium and solid-state designs are among the most promising.\n\nIf they reach the market, these batteries could reshape everything from phones to electric cars to the power grid. The remaining hurdle is scaling lab success into affordable mass production.",$P],
  ['How Tiny Satellites Are Democratising Space Research','Science',"Satellites the size of a shoebox are opening space to universities, start-ups, and even schools. Cheap to build and launch, they are gathering data that once required enormous budgets.\n\nThese small satellites monitor crops, track weather, and study the atmosphere. Their rise marks a shift from space as the domain of a few nations to a shared, accessible frontier.",$P],
  ["The Ocean's Hidden Role in Regulating the Climate",'Science',"The ocean absorbs a huge share of the planet's heat and carbon, quietly buffering the climate. Scientists are only beginning to map how these vast systems work.\n\nUnderstanding ocean currents and their limits is crucial for accurate climate predictions. New sensors and floating robots are giving researchers their clearest picture yet of this hidden engine.",$P],

  // Sports
  ['Underdogs Steal the Spotlight in a Wild Playoff Season','Sports',"This season has been defined by surprises, with lower-ranked teams toppling favourites and thrilling fans. Momentum and belief, it turns out, can outweigh reputation.\n\nCommentators point to deeper squads and smarter coaching as reasons the gap has narrowed. Whatever the cause, the unpredictability has made for some of the most watched games in years.",$P],
  ['Data Analytics Is Changing How Teams Draft Talent','Sports',"Gut instinct still matters in scouting, but data now sits alongside it. Teams analyse thousands of metrics to spot undervalued players and predict who will thrive.\n\nThe approach has produced surprising success stories and a few expensive misses. The best organisations blend the numbers with the human judgement of experienced scouts.",$P],
  ['The Marathon Boom: Why Everyone Is Suddenly Running','Sports',"Race entries are selling out faster than ever as running surges in popularity. For many, a marathon is less about competition and more about a personal challenge.\n\nRunning clubs and apps have built communities that keep people motivated. The sport's low cost and simplicity make it one of the most accessible ways to get fit.",$P],
  ["Inside the Rise of Women's Professional Leagues",'Sports',"Women's professional sport is enjoying a surge in attendance, investment, and television coverage. Sold-out stadiums are rewriting old assumptions about demand.\n\nSponsors and broadcasters are following the audience, creating a virtuous circle of visibility and growth. Players say the momentum is long overdue and, finally, unmistakable.",$P],

  // Entertainment
  ['Streaming Wars Enter a New, More Selective Era','Entertainment',"After years of signing up for every service, viewers are becoming choosier. Rising prices and crowded libraries have made people rethink how many subscriptions they really need.\n\nStreaming companies are responding with bundles, cheaper ad-supported tiers, and a sharper focus on must-watch originals. Quality, once again, is beating quantity.",$P],
  ['Why Practical Effects Are Making a Comeback in Film','Entertainment',"As audiences grow weary of weightless digital spectacle, filmmakers are returning to practical effects: real sets, models, and stunts. The tangible quality is winning praise.\n\nMany directors now blend the two, using digital tools to enhance rather than replace what the camera captures. The result often feels more grounded and memorable.",$P],
  ['Indie Games Are Outshining Big-Budget Blockbusters','Entertainment',"Small studios are producing some of the most inventive and beloved games of the year. Freed from blockbuster expectations, they take creative risks that big publishers avoid.\n\nDigital storefronts let these games find their audience directly. Word of mouth does the rest, turning modest projects into surprise hits.",$P],
  ['The Vinyl Revival Shows No Signs of Slowing','Entertainment',"Vinyl records, once written off as obsolete, are outselling many digital formats. Fans cite warmer sound and the pleasure of owning something physical.\n\nArtists have embraced the trend with special editions and colourful pressings. In an age of endless streaming, a record on a turntable offers a slower, more deliberate way to listen.",$P],

  // Travel
  ['Slow Travel: The Case for Staying Longer in One Place','Travel',"Rather than racing to tick off landmarks, more travellers are choosing to linger. Slow travel means staying longer, going deeper, and actually getting to know a place.\n\nThe rewards are richer experiences and a lighter footprint. Locals notice the difference too, as visitors support neighbourhood cafes and markets instead of only the busiest sights.",$P],
  ['Underrated Cities That Deserve a Spot on Your List','Travel',"The world's famous destinations are wonderful, but they are also crowded and expensive. A little research turns up smaller cities with just as much character and far fewer queues.\n\nThese places often reward curiosity with authentic food, friendly welcomes, and stories the guidebooks miss. Sometimes the best trips are the ones nobody expected.",$P],
  ['How to Travel More Sustainably Without Spending More','Travel',"Sustainable travel does not have to mean expensive eco-lodges. Simple choices, such as taking the train, packing light, and eating local, cut both cost and impact.\n\nTravellers are also staying longer in fewer places, which reduces flights and deepens the experience. Small, thoughtful decisions add up across a whole journey.",$P],
  ['Night Trains Are Making a Comeback Across Europe','Travel',"Sleeper trains, once fading into history, are being revived as a comfortable, low-carbon alternative to short flights. New routes are connecting major cities overnight.\n\nPassengers board in one city and wake in another, skipping airports entirely. For many, the romance of the railway is part of the appeal.",$P],

  // Automotive
  ['Electric SUVs Are Winning Over Skeptical Buyers','Automotive',"Once a niche choice, electric SUVs are now among the best-selling vehicles in many markets. Longer range and faster charging have eased the doubts of cautious buyers.\n\nRoomy interiors and lower running costs seal the deal for families. As charging networks expand, the practical gap with petrol models continues to shrink.",$P],
  ['The Used-Car Market Finally Cools Off','Automotive',"After a period of soaring prices, the used-car market is settling back toward normal. More supply and steadier demand are giving buyers room to negotiate again.\n\nExperts advise patience and research, since prices vary widely by model. For those who waited out the frenzy, better deals are finally returning.",$P],
  ['Why Carmakers Are Betting on Software, Not Just Steel','Automotive',"Modern cars are increasingly defined by their software as much as their engines. Updates delivered over the air can add features and fix issues long after purchase.\n\nThis shift is turning carmakers into technology companies, with new revenue from subscriptions and services. Critics worry about complexity, but the direction of travel is clear.",$Q],
  ['Classic Cars Meet Electric Hearts in the Restomod Boom','Automotive',"A growing movement is fitting classic car bodies with modern electric drivetrains. The result pairs timeless design with quiet, reliable performance.\n\nEnthusiasts are divided over whether it honours or alters the originals. Either way, these restomods are keeping beloved shapes on the road for a new era.",$P],

  // World
  ['Cities Worldwide Rethink Streets for People, Not Cars','World',"From Paris to Bogota, cities are reclaiming road space for pedestrians, cyclists, and greenery. Wider pavements and car-free zones are changing the feel of urban life.\n\nEarly results show cleaner air and livelier high streets. The transition is not without friction, but momentum is building across very different cities.",$P],
  ['Global Literacy Hits a Record High, Report Finds','World',"A new report shows global literacy at its highest level ever, driven by expanded schooling and digital access. The gains are especially strong among young women.\n\nChallenges remain in the poorest regions, where resources are stretched. Still, the long-term trend is one of steady, hard-won progress.",$P],
  ['Coastal Communities Adapt to a Changing Shoreline','World',"Communities along the world's coasts are finding creative ways to live with rising and shifting waters. Restored wetlands and smarter building are part of the answer.\n\nLocal knowledge is proving as valuable as engineering. The most successful projects combine both, protecting homes while preserving a way of life.",$P],

  // Food
  ['The Fermentation Craze Moves From Trend to Staple','Food',"Fermented foods, from kimchi to kombucha, have moved from trendy to everyday. Home cooks are embracing the science and flavour of controlled fermentation.\n\nBeyond taste, many are drawn to the reported benefits for digestion. Starter cultures are being passed between friends much like treasured recipes.",$P],
  ['Weeknight Cooking Gets a Global Makeover','Food',"Busy home cooks are reaching beyond familiar dishes, borrowing spices and techniques from around the world. A well-stocked pantry turns a quick dinner into something exciting.\n\nOnline recipes and short videos have made once-intimidating cuisines approachable. The kitchen has become a place of small, delicious experiments.",$P],
  ['Why Everyone Is Talking About Regional Street Food','Food',"Street food is having a moment, celebrated for its authenticity and bold flavours. Markets and food halls are showcasing regional specialities to eager crowds.\n\nFor many cooks, these dishes carry family history and local pride. Diners get more than a meal; they get a taste of a place and its people.",$P],

  // Environment
  ['Rooftop Solar Reaches a Tipping Point in Cities','Environment',"Falling prices and better technology have made rooftop solar a mainstream choice for homes and businesses. In sunny cities, panels now pay for themselves within years.\n\nCombined with home batteries, solar is giving people more control over their energy. The rooftops of entire neighbourhoods are quietly becoming small power stations.",$P],
  ['Rewilding Projects Bring Lost Species Back Home','Environment',"Across the world, rewilding efforts are restoring habitats and reintroducing species that had vanished. Beavers, bison, and birds of prey are returning to old ranges.\n\nThe ripple effects can be dramatic, as restored ecosystems recover balance. Supporters see rewilding as a hopeful, hands-off way to heal the land.",$P],
  ['The Slow, Steady Win of Community Recycling','Environment',"Recycling rarely makes headlines, but community programmes are quietly diverting mountains of waste from landfill. Clear rules and local buy-in make the difference.\n\nThe best schemes focus on reducing and reusing first, with recycling as the backstop. Small daily choices, multiplied across a community, add up to real impact.",$Q],
];

/* ---- 4. Insert the articles ---- */
/* Topic-relevant cover photos (Unsplash), two per category: [a, b] */
$covers = [
  'technology'    => ['photo-1518770660439-4636190af475', 'photo-1461749280684-dccba630e2f6'],
  'business'      => ['photo-1486406146926-c627a92ad1ab', 'photo-1556761175-b413da4baf72'],
  'finance'       => ['photo-1611974789855-9c2a0a7236a3', 'photo-1579621970563-ebec7560ff3e'],
  'health'        => ['photo-1576091160399-112ba8d25d1d', 'photo-1571019613454-1cb2f99b2d8b'],
  'science'       => ['photo-1451187580459-43490279c0fa', 'photo-1532094349884-543bc11b234d'],
  'sports'        => ['photo-1461896836934-ffe607ba8211', 'photo-1517649763962-0c623066013b'],
  'entertainment' => ['photo-1489599849927-2ee91cede3ba', 'photo-1470229722913-7c0e2dbbafd3'],
  'travel'        => ['photo-1488646953014-85cb44e25828', 'photo-1476514525535-07fb3b4ae5f1'],
  'automotive'    => ['photo-1503376780353-7e6692767b70', 'photo-1449965408869-eaa3f722e40d'],
  'food'          => ['photo-1504674900247-0877df9cc836', 'photo-1512621776951-a57141f2eefd'],
  'environment'   => ['photo-1441974231531-c6227db76b6e', 'photo-1466611653911-95081537e5b7'],
  'world'         => ['photo-1495020689067-958852a7765e', 'photo-1502602898657-3e91760cbb34'],
];
$stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, image_path, category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
$cat_counter = [];
$i = 0; $inserted = 0; $pending = 0;
foreach ($articles as $a) {
    $title = $a[0]; $cat = $a[1]; $body = $a[2]; $status = isset($a[3]) ? $a[3] : $P;
    // alternate the two topic-relevant covers per category
    $key = strtolower($cat);
    $cat_counter[$key] = isset($cat_counter[$key]) ? $cat_counter[$key] + 1 : 0;
    $variant = $cat_counter[$key] % 2;
    $photo = isset($covers[$key]) ? $covers[$key][$variant] : $covers['world'][0];
    $image = "https://images.unsplash.com/{$photo}?auto=format&fit=crop&w=800&q=80";
    // author round-robin
    $uid = $author_ids[$i % count($author_ids)];
    // date spread over last ~90 days
    $created = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days -' . rand(0, 1439) . ' minutes'));

    $stmt->bind_param("issssss", $uid, $title, $body, $image, $cat, $status, $created);
    $stmt->execute();
    $inserted++; if ($status === $Q) $pending++;
    $i++;
}
$stmt->close();
echo "Inserted $inserted articles ($pending pending, " . ($inserted - $pending) . " published).\n";

/* ---- 5. A few sample comments on published articles ---- */
$pub_ids = [];
$r = $conn->query("SELECT id FROM posts WHERE status='published' ORDER BY created_at DESC LIMIT 12");
while ($row = $r->fetch_assoc()) $pub_ids[] = (int)$row['id'];

$sample_comments = [
    "Really well explained — thanks for sharing this!",
    "This gave me a completely new perspective on the topic.",
    "Great read. I'd love to see a follow-up piece.",
    "Interesting points, especially the part near the end.",
    "Bookmarking this one. Very timely and useful.",
    "Solid article. Shared it with a few colleagues already.",
    "I was just wondering about this — perfect timing.",
];
$cstmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, ?)");
$cc = 0;
foreach ($sample_comments as $k => $text) {
    if (empty($pub_ids)) break;
    $pid = $pub_ids[$k % count($pub_ids)];
    $uid = $author_ids[($k + 1) % count($author_ids)];
    $cdate = date('Y-m-d H:i:s', strtotime('-' . rand(0, 20) . ' days -' . rand(0, 1439) . ' minutes'));
    $cstmt->bind_param("iiss", $pid, $uid, $text, $cdate);
    $cstmt->execute();
    $cc++;
}
$cstmt->close();
echo "Inserted $cc sample comments.\n";

/* ---- 6. Summary ---- */
$byCat = $conn->query("SELECT category, COUNT(*) c FROM posts WHERE status='published' GROUP BY category ORDER BY c DESC");
echo "\nPublished articles by category:\n";
while ($row = $byCat->fetch_assoc()) echo sprintf("  %-16s %d\n", $row['category'], $row['c']);
$conn->close();
echo "\nDone.\n";
