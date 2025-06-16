<?php
// index.php (Home Page)
require_once 'server/config.php'; // Include your database connection

// --- Helper function to build a hierarchical menu array ---
function build_menu_tree($items, $parentId = NULL) {
    $branch = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = build_menu_tree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $branch[] = $item;
        }
    }
    return $branch;
}

// --- Function to render menu HTML recursively ---
function render_menu_html($menu_items) {
    $html = '';
    foreach ($menu_items as $item) {
        if ($item['status'] === 'active') { // Only render active menu items
            if (isset($item['children']) && !empty($item['children'])) {
                $html .= '<li class="has-submenu">';
                $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a>';
                $html .= '<ul class="submenu">';
                $html .= render_menu_html($item['children']); // Recursive call for children
                $html .= '</ul>';
                $html .= '</li>';
            } else {
                $html .= '<li><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
            }
        }
    }
    return $html;
}

// --- Fetch Menu Items ---
$menu_items_raw = [];
$sql_menu = "SELECT id, title, url, parent_id, item_order, status FROM menu_items ORDER BY item_order ASC";
if ($result = mysqli_query($link, $sql_menu)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items_raw[] = $row;
    }
    mysqli_free_result($result);
}
// Build the hierarchical menu tree
$dynamic_menu_html = render_menu_html(build_menu_tree($menu_items_raw));


// --- Fetch Sliders ---
$sliders = [];
$sql_sliders = "SELECT title, image_url, link_url FROM newssliders WHERE status = 'active' ORDER BY created_at DESC";
if ($result = mysqli_query($link, $sql_sliders)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sliders[] = $row;
    }
    mysqli_free_result($result);
}

// --- Fetch Latest News ---
$news_articles = [];
// Placeholder for news articles without specific images or if image_url is empty
$placeholder_news_image = 'https://via.placeholder.com/300x200?text=University+News';
// Added image_url to SELECT
$sql_news = "SELECT title, description, available_at, image_url FROM news ORDER BY available_at DESC LIMIT 6";
if ($result = mysqli_query($link, $sql_news)) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Truncate description for excerpt
        $row['excerpt'] = (strlen($row['description']) > 150) ? substr($row['description'], 0, 147) . '...' : $row['description'];
        $news_articles[] = $row;
    }
    mysqli_free_result($result);
}

// --- Fetch Upcoming Events ---
$events = [];
$sql_events = "SELECT title, description, date, location FROM events  ORDER BY date ASC LIMIT 4";
if ($result = mysqli_query($link, $sql_events)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    mysqli_free_result($result);
}

// Close database connection
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>University CMS - Home</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap is linked in your CSS, but if you need JS components, you'd add it here -->
  <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->
  <style>
    /* Base Styles */
:root {
  --primary-color: #1e3a8a;
  --secondary-color: #3b82f6;
  --accent-color: #f59e0b;
  --text-color: #333;
  --text-light: #666;
  --background-color: #fff;
  --background-alt: #f8fafc;
  --border-color: #e5e7eb;
  --success-color: #10b981;
  --danger-color: #ef4444;
  --warning-color: #f59e0b;
  --info-color: #3b82f6;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --transition: all 0.3s ease;
  --radius: 0.375rem;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: var(--text-color);
  background-color: var(--background-color);
}

a {
  color: var(--primary-color);
  text-decoration: none;
  transition: var(--transition);
}

a:hover {
  color: var(--secondary-color);
}

ul {
  list-style: none;
}

img {
  max-width: 100%;
  height: auto;
}

.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.btn-more {
  display: inline-block;
  background-color: var(--primary-color);
  color: white;
  padding: 0.5rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 500;
  transition: var(--transition);
  margin-top: 1.5rem;
}

.btn-more:hover {
  background-color: var(--secondary-color);
  color: white;
}

/* Header Styles */
header {
  background-color: var(--background-color);
  box-shadow: var(--shadow);
  position: sticky;
  top: 0;
  z-index: 100;
}

header .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
}

.logo {
  display: flex;
  align-items: center;
}

#menu-toggle {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
}

#menu-toggle span {
  display: block;
  width: 25px;
  height: 3px;
  background-color: var(--text-color);
  margin: 5px 0;
  transition: var(--transition);
}

#main-nav ul {
  display: flex;
  gap: 1.5rem;
}

#main-nav ul li a {
  font-weight: 500;
  padding: 0.5rem;
  position: relative;
}

#main-nav ul li a::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background-color: var(--primary-color);
  transition: var(--transition);
}

#main-nav ul li a:hover::after,
#main-nav ul li.active a::after {
  width: 100%;
}

/* Slider Styles */
#slider {
  position: relative;
  overflow: hidden;
  height: 700px;
  background-color: var(--background-alt);
}

.slider-container {
  position: relative;
  height: 100%;
}

.slider-wrapper {
  display: flex;
  height: 100%;
  transition: transform 0.5s ease;
}

.slider-item {
  flex: 0 0 100%;
  height: 100%;
  position: relative;
}

.slider-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.slider-content {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 2rem;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
  color: white;
}

.slider-content h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.slider-content p {
  font-size: 1rem;
}

.slider-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background-color: rgba(255, 255, 255, 0.5);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
  z-index: 10;
}

.slider-arrow:hover {
  background-color: rgba(255, 255, 255, 0.8);
}

.slider-arrow.prev {
  left: 20px;
}

.slider-arrow.next {
  right: 20px;
}

.slider-dots {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 10px;
}

.slider-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: var(--transition);
}

.slider-dot.active,
.slider-dot:hover {
  background-color: white;
}

/* News Section Styles */
#news {
  padding: 3rem 1rem;
}

#news h2 {
  font-size: 2rem;
  margin-bottom: 2rem;
  text-align: center;
  position: relative;
}

#news h2::after {
  content: "";
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background-color: var(--primary-color);
}

.news-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.news-card {
  background-color: var(--background-color);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: var(--transition);
}

.news-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.news-image {
  height: 200px;
  overflow: hidden;
}

.news-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: var(--transition);
}

.news-card:hover .news-image img {
  transform: scale(1.05);
}

.news-content {
  padding: 1.5rem;
}

.news-date {
  color: var(--text-light);
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.news-title {
  font-size: 1.25rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
}

.news-excerpt {
  color: var(--text-light);
  margin-bottom: 1rem;
}

/* Events Section Styles */
#events {
  padding: 3rem 1rem;
  background-color: var(--background-alt);
}

#events h2 {
  font-size: 2rem;
  margin-bottom: 2rem;
  text-align: center;
  position: relative;
}

#events h2::after {
  content: "";
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background-color: var(--primary-color);
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.event-card {
  display: flex;
  background-color: var(--background-color);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: var(--transition);
}

.event-card:hover {
  transform: translateX(5px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.event-date-box { /* Renamed from .event-date to avoid conflict with HTML5 date input */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 100px;
  background-color: var(--primary-color);
  color: white;
  padding: 1rem;
}

.event-day {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
}

.event-month {
  font-size: 1rem;
  text-transform: uppercase;
}

.event-content {
  padding: 1.5rem;
  flex: 1;
}

.event-title {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.event-details {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 0.75rem;
  color: var(--text-light);
  font-size: 0.875rem;
}

.event-location,
.event-time {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.event-description {
  color: var(--text-light);
}

/* Footer Styles */
footer {
  background-color: var(--primary-color);
  color: white;
  padding: 3rem 0 1rem;
}
.search-bar {
            margin-bottom: 30px;
            text-align: center;
        
        }

.search-bar input[type="text"] {
            width: 100%;
            max-width: 1000px;
            padding: 12px 20px;
            border: 1px solid #cbd5e0;
            border-radius: 25px;
            font-size: 1.1em;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

.search-bar input[type="text"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
.footer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.footer-col h3 {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
  position: relative;
}

.footer-col h3::after {
  content: "";
  position: absolute;
  bottom: -8px;
  left: 0;
  width: 50px;
  height: 2px;
  background-color: var(--accent-color);
}

.footer-col ul li {
  margin-bottom: 0.75rem;
}

.footer-col ul li a {
  color: rgba(255, 255, 255, 0.8);
  transition: var(--transition);
}

.footer-col ul li a:hover {
  color: white;
  padding-left: 5px;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-links a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: white;
  transition: var(--transition);
}

.social-links a:hover {
  background-color: var(--accent-color);
  transform: translateY(-3px);
}

.copyright {
  text-align: center;
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
}

/* Responsive Styles */
@media (max-width: 768px) {
  .navbar-toggle {
    display: block;
  }

  .navbar-items {
    position: absolute;
    top: 70px; /* Adjust based on your actual navbar height */
    right: 20px;
    background-color: #002147;
    width: 200px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 5px;
    padding: 10px 0;
    display: none; /* Hidden by default on mobile */
    flex-direction: column;
  }

  .navbar-items.active {
    display: flex;
  }

  .submenu {
    position: static; /* Submenus stack vertically */
    display: none;
    background: #f8f9fa; /* Lighter background for submenus on mobile */
    color: black;
    box-shadow: none;
    padding-left: 15px; /* Indent submenus */
  }

  .has-submenu.open .submenu {
    display: block;
  }

  #slider {
    height: 350px;
  }

  .event-card {
    flex-direction: column;
  }

  .event-date-box { /* Renamed from .event-date */
    flex-direction: row;
    gap: 0.5rem;
    padding: 0.75rem;
    width: 100%;
    min-width: auto;
  }

  .event-day,
  .event-month {
    font-size: 1.25rem;
  }
}

@media (max-width: 576px) {
  #slider {
    height: 250px;
  }

  .slider-content h2 {
    font-size: 1.5rem;
  }

  .slider-content p {
    font-size: 0.875rem;
  }

  .news-grid {
    grid-template-columns: 1fr;
  }
}
/* Navbar Base Styling */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background-color: #002147; /* Dark Blue */
  color: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.navbar-logo img {
  height: 100px;
  width: 600px;
  border radius:80%;

}

/* Menu Items */
.navbar-items {
  list-style: none;
  display: flex;
  gap: 20px;
}

.navbar-items li {
  position: relative;
}

.navbar-items li a {
  text-decoration: none;
  color: white;
  font-size: 16px;
  font-weight: bold;
  padding: 10px 15px;
  border-radius: 5px;
  transition: 0.3s ease-in-out;
  display: block;
}

.navbar-items li a:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

/* Dropdown Styling */
.has-submenu > a::after {
  content: " ▼";
  font-size: 12px;
  margin-left: 5px;
}

.submenu {
  display: none;
  position: absolute;
  background-color: white;
  color: black;
  top: 100%;
  left: 0;
  min-width: 200px;
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 5px;
  padding: 5px 0;
  z-index: 1000;
}

.submenu li {
  display: block;
}

.submenu li a {
  display: block;
  padding: 10px;
  color: black;
  text-align: left;
}

.submenu li a:hover {
  background-color: #ddd;
}

/* Show Submenu on Hover */
.has-submenu:hover > .submenu {
  display: block;
}

/* Mobile Menu */
.navbar-toggle {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
}

.navbar-toggle span {
  display: block;
  width: 25px;
  height: 3px;
  background-color: white;
  margin: 5px;
  transition: 0.3s;
}
.search-bar {
            margin-bottom: 30px;
            text-align: center;
           width: 800px;
        }

.search-bar input[type="text"] {
            width: 100%;
           
            padding: 12px 20px;
            border: 1px solid #cbd5e0;
            border-radius: 25px;
            font-size: 1.1em;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

.search-bar input[type="text"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }


@media (max-width: 768px) {
  .navbar-toggle {
      display: block;
  }

  .navbar-items {
      display: none;
      flex-direction: column;
      position: absolute;
      top: 70px;
      right: 20px;
      background-color: #002147;
      width: 200px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      border-radius: 5px;
      padding: 10px 0;
  }

  .navbar-items.active {
      display: flex;
  }




  .submenu {
      position: static;
      display: none;
      background: #f8f9fa;
      color: black;
      box-shadow: none;
  }

  .has-submenu.open .submenu {
      display: block;
  }
}
  </style>
</head>
<body>
  <header>
    <div class="navbar">
      <div class="navbar-logo">
        <img src="image/UB logo.png" alt="University Logo">
      </div>
      <div>

      </div>
      
      <div class="search-bar">
        <input type="text" id="gymSearch" placeholder="Search News,Event...">
      </div>
      
      <div class="navbar-menu">
        <button id="navbar-toggle" class="navbar-toggle" aria-label="Toggle Menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <ul class="navbar-items" id="menu-items">
          <?php echo $dynamic_menu_html; ?>
        </ul>
      </div>
    </div>
  </header>

  <section id="slider">
    <div class="slider-container">
      <div class="slider-wrapper">
        <?php if (!empty($sliders)): ?>
            <?php foreach ($sliders as $index => $slider): ?>
            <div class="slider-item">
                <img src="<?php echo htmlspecialchars($slider['image_url']); ?>" alt="<?php echo htmlspecialchars($slider['title']); ?>">
                <div class="slider-content">
                    <h2><?php echo htmlspecialchars($slider['title']); ?></h2>
                    <?php if (!empty($slider['link_url'])): ?>
                        <p><a href="<?php echo htmlspecialchars($slider['link_url']); ?>" class="btn-more">Learn More</a></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="slider-item">
                <img src="https://via.placeholder.com/1200x700?text=No+Sliders+Available" alt="No Sliders">
                <div class="slider-content">
                    <h2>Welcome to Our University</h2>
                    <p>No active sliders found.</p>
                </div>
            </div>
        <?php endif; ?>
      </div>
      <?php if (count($sliders) > 1): // Only show arrows/dots if more than one slider ?>
      <button class="slider-arrow prev" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>
      <button class="slider-arrow next" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>
      <div class="slider-dots">
        <?php foreach ($sliders as $index => $slider): ?>
            <span class="slider-dot" data-index="<?php echo $index; ?>"></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <main>
    <section id="news" class="container">
      <h2>Latest News</h2>
      <div class="news-grid">
        <?php if (!empty($news_articles)): ?>
            <?php foreach ($news_articles as $news): ?>
            <div class="news-card">
                <div class="news-image">
                    <!-- Use news['image_url'] if available, otherwise fallback to placeholder -->
                    <img src="<?php echo htmlspecialchars(!empty($news['image_url']) ? $news['image_url'] : $placeholder_news_image); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                </div>
                <div class="news-content">
                    <div class="news-date"><?php echo htmlspecialchars(date('F j, Y', strtotime($news['available_at']))); ?></div>
                    <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                    <p class="news-excerpt"><?php echo htmlspecialchars($news['excerpt']); ?></p>
                    <a href="news.php" class="btn-more">Read More</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No news articles found.</p>
        <?php endif; ?>
      </div>
      <a href="news.php" class="btn-more">View All News</a>
    </section>

    <section id="events" class="container">
      <h2>Upcoming Events</h2>
      <div class="events-list">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-date-box">
                    <span class="event-day"><?php echo htmlspecialchars(date('d', strtotime($event['date']))); ?></span>
                    <span class="event-month"><?php echo htmlspecialchars(date('M', strtotime($event['date']))); ?></span>
                </div>
                <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <div class="event-details">
                        <div class="event-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                        <div class="event-time"><i class="fas fa-clock"></i> <?php echo htmlspecialchars(date('h:i A', strtotime($event['date']))); ?></div>
                    </div>
                    <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                    <a href="events.php" class="btn-more">Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No upcoming events found.</p>
        <?php endif; ?>
      </div>
      <a href="events.php" class="btn-more">View All Events</a>
    </section>
  </main>

  <footer>
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <h3>Contact Us</h3>
          <address>
            <div class="container"  id="university_name">University Of Buea</div>
            <br>
            <div class="container" id="university_location">Buea , Cameroon</div>
            <br>
            <div class="container"  id="university_phone">phone:(123) 456-7890</div>
            <br>
            <div class="container" id="university_email">info@ubuea.cm</div>
          </address>
        </div>
        <div class="footer-col">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Academics</a></li>
            <li><a href="#">Admissions</a></li>
            <li><a href="#">Campus Life</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h3>Follow Us</h3>
          <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
      </div>
      <div class="copyright">
        <p>&copy; 2025 university of Buea. All Rights Reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    // js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Navbar Toggle for Mobile ---
    const navbarToggle = document.getElementById('navbar-toggle');
    // The HTML element with id="menu-items" is the <ul> for navigation
    const menuItems = document.getElementById('menu-items'); 

    if (navbarToggle && menuItems) {
        navbarToggle.addEventListener('click', () => {
            menuItems.classList.toggle('active');
        });

        // Close menu if a submenu item is clicked or if clicked outside
        document.addEventListener('click', (event) => {
            if (!navbarToggle.contains(event.target) && !menuItems.contains(event.target)) {
                menuItems.classList.remove('active');
            }
        });

        // Toggle submenu for mobile (if you want them to open on click)
        // This needs to target the dynamically generated .has-submenu elements
        document.querySelectorAll('.navbar-items .has-submenu > a').forEach(item => {
            item.addEventListener('click', (e) => {
                // Check if the click is on the parent link itself, not a child element within it
                if (e.target === item || item.contains(e.target)) {
                    if (window.innerWidth <= 768) { // Apply only on mobile
                        e.preventDefault(); // Prevent default link behavior
                        const parentLi = item.closest('.has-submenu');
                        parentLi.classList.toggle('open');
                    }
                }
            });
        });
    }

    // --- Slider Functionality ---
    // Select the wrapper that contains all slider items
    const sliderWrapper = document.querySelector('.slider-wrapper');
    // Select all individual slider items (these are generated by PHP)
    const sliderItems = document.querySelectorAll('.slider-item'); 
    const prevArrow = document.querySelector('.slider-arrow.prev');
    const nextArrow = document.querySelector('.slider-arrow.next');
    // Select the container for slider dots
    const sliderDotsContainer = document.querySelector('.slider-dots');
    // Select all individual slider dots (these are generated by PHP)
    const sliderDots = document.querySelectorAll('.slider-dot'); 

    // Only initialize slider if there's a wrapper and at least one slide
    if (sliderWrapper && sliderItems.length > 0) {
        let currentIndex = 0;
        const totalSlides = sliderItems.length;

        // Function to update slider position
        const updateSlider = () => {
            const offset = -currentIndex * 100; // Calculate offset for current slide
            sliderWrapper.style.transform = `translateX(${offset}%)`; // Move the slider wrapper
            updateDots(); // Update active dot
        };

        // Function to update active dot
        const updateDots = () => {
            sliderDots.forEach((dot, index) => {
                if (index === currentIndex) {
                    dot.classList.add('active'); // Add 'active' class to current dot
                } else {
                    dot.classList.remove('active'); // Remove 'active' class from others
                }
            });
        };

        // Event listener for next arrow
        if (nextArrow) { // Check if the next arrow exists (only if totalSlides > 1)
            nextArrow.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % totalSlides; // Cycle to the next slide
                updateSlider();
            });
        }

        // Event listener for previous arrow
        if (prevArrow) { // Check if the previous arrow exists (only if totalSlides > 1)
            prevArrow.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + totalSlides) % totalSlides; // Cycle to the previous slide
                updateSlider();
            });
        }

        // Event listeners for dots
        if (sliderDotsContainer) { // Check if dots container exists
            sliderDotsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('slider-dot')) { // Ensure click was on a dot
                    const index = parseInt(e.target.dataset.index); // Get index from data-index attribute
                    if (!isNaN(index)) {
                        currentIndex = index; // Set current slide to clicked dot's index
                        updateSlider();
                    }
                }
            });
        }

        // Auto-advance slider (optional)
        let autoSlideInterval;
        const startAutoSlide = () => {
            if (totalSlides > 1) { // Only auto-slide if there's more than one slide
                autoSlideInterval = setInterval(() => {
                    currentIndex = (currentIndex + 1) % totalSlides;
                    updateSlider();
                }, 5000); // Change slide every 5 seconds
            }
        };

        const stopAutoSlide = () => {
            clearInterval(autoSlideInterval);
        };

        // Start auto-slide on load
        startAutoSlide();

        // Pause auto-slide on hover over the slider area
        sliderWrapper.addEventListener('mouseenter', stopAutoSlide);
        sliderWrapper.addEventListener('mouseleave', startAutoSlide);

        // Initialize slider on load (set initial position and active dot)
        updateSlider();
    }
});
  </script>
</body>
</html>