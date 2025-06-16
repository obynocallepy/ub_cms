import Eventbus from "./modules/Eventbus.js";
import request from "./modules/request.js"
import { serializeForm } from "./modules/serialize.js"

const requestUrl = 'server/request.php';
/**
 * @type {'delete'|'edit'|'add'|'get'}
 */
var typeofAction = 'get';
var idofAction = null;
const eventbus = new Eventbus();


let events = [
  { id: 1, title: "Annual Science Fair", date: "2025-04-15", location: "Science Building", status: "Upcoming" },
  { id: 2, title: "Spring Graduation Ceremony", date: "2025-05-20", location: "Main Auditorium", status: "Upcoming" },
  { id: 3, title: "Faculty Research Symposium", date: "2025-04-05", location: "Conference Center", status: "Upcoming" },
  { id: 4, title: "Alumni Networking Event", date: "2025-03-25", location: "Student Union", status: "Past" }
];

let newsArticles = [
  /* { id: 1, title: "University Receives Major Research Grant", category: "Research", date: "2025-03-15", status: "Published" },
  { id: 2, title: "New Academic Programs Announced for Fall", category: "Academics", date: "2025-03-10", status: "Published" },
  { id: 3, title: "Student Team Wins National Competition", category: "Student Life", date: "2025-03-05", status: "Published" },
  { id: 4, title: "Upcoming Campus Renovation Plans", category: "Campus", date: "2025-03-01", status: "Draft" } */
];


let sliders = [
  { id: 1, title: "Welcome to University", description: "Main homepage slider with campus view", img: "https://imgs.search.brave.com/uRWLYFwNSpmoW5xuaByQopQs0KPYSsD5sltwuQJmI0s/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly91cGxv/YWQud2lraW1lZGlh/Lm9yZy93aWtpcGVk/aWEvY29tbW9ucy9l/L2U5L0lNRy0yMDE5/MDYyNi1XQTAwMzIu/anBn?height=150&width=300" },
  { id: 2, title: "Research Excellence", description: "Highlighting research achievements", img: "https://imgs.search.brave.com/bja5AdHZOkm6s2ccP8Sb7D5bo7VktFE-RheYsrEHxhY/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly93d3cu/ZXhlbXBsYXJzLmhl/YWx0aC8tL21lZGlh/L2ltYWdlcy9lZ2gv/bmV3cy9leGVtcGxh/cnMtZGlhZ25vc3Rp/Y3MtbGFiY29wLmpw/Zz9oPTg2MCZtdz0x/MjkwJnc9MTI5MCZo/YXNoPTM1NDRFMTVG/ODA4OUU3NThGMUQw/RjVGOEU1RkU3RjI0?height=150&width=300" },
  { id: 3, title: "Student Life", description: "Campus activities and student engagement", img: "https://imgs.search.brave.com/1Lmhit9S1kkA0nLLx2_KbK2HGjbfEX4571zfCoLa8bg/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9zdHVk/ZW50bGlmZS51dGsu/ZWR1L211bHRpY3Vs/dHVyYWwvd3AtY29u/dGVudC91cGxvYWRz/L3NpdGVzLzYvMjAy/Mi8xMC9CQ1BDMl84/MDB4NjAwLmpwZw?height=150&width=300" }
];

function FetchNews(){
  newsArticles = []
  renderNews();
    request({
      url: 'server/request.php',
      method: 'POST',
      data: {
        getContentsAll: true
      },
      success: (data)=>{
        if(data.error){
          alert(data.error.message);
          return
        }

        newsArticles = data.data;
        renderNews();
        console.log('articles', data)
      }
    })
  }




// Function to render events
function renderEvents(filter = "") {
    document.getElementById("event-items").innerHTML = ""; // Clear existing events

    let filteredEvents = events.filter(event => 
        event.title.toLowerCase().includes(filter.toLowerCase())
    );

    filteredEvents.forEach(event => {
        const row = document.createElement("tr");
        row.dataset.id = event.id;
        let status = event.availableAt?(Date.now() - (new Date(event.availableAt)).getTime() ) - 5000 > 0?'upcoming':'published':'draft';
        row.innerHTML = `
            <td><div class="drag-handle"><i class="fas fa-grip-lines"></i></div></td>
            <td>${event.title}</td>
            <td>${event.date}</td>
            <td>All</td>
            <td><span class="status ${status}">${status}</span></td>
            <td>
                <button class="btn icon edit-btn" data-id="${event.id}"><i class="fas fa-edit"></i></button>
                <button class="btn icon danger delete-btn" data-id="${event.id}"><i class="fas fa-trash"></i></button>
            </td>
        `;
        document.getElementById("event-items").appendChild(row);
    });

    attachEventListeners();
}

// Function to attach event listeners to edit and delete buttons
function attachEventListeners() {
    document.querySelectorAll("#event-items .delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            const eventId = parseInt(this.dataset.id);
            deleteEvent(eventId);
        });
    });

    document.querySelectorAll("#event-items .edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            const eventId = parseInt(this.dataset.id);
            editEvent(eventId);
        });
    });
}


function initeventmanagement(){
  const eventContainer = document.getElementById("event-items");
    const addEventButton = document.getElementById("add-event");
    const searchInput = document.getElementById("search-event");
    const searchButton = document.getElementById("search-button");

    // Sample event data (can be fetched from an API)


    // Function to add a new event
    addEventButton.addEventListener("click", function () {
        const newId = events.length + 1;
        const newEvent = {
            id: newId,
            title: `New Event ${newId}`,
            date: new Date().toISOString().split("T")[0], // Today's date
            location: "TBA",
            status: "Upcoming"
        };
        events.push(newEvent);
        renderEvents();
    });

    // Function to delete an event
    function deleteEvent(id) {
        events = events.filter(event => event.id !== id);
        renderEvents();
    }

    // Function to edit an event (This can be expanded with a modal)
    function editEvent(id) {
        const event = events.find(event => event.id === id);
        if (event) {
            const newTitle = prompt("Enter new title:", event.title);
            if (newTitle) {
                event.title = newTitle;
                renderEvents();
            }
        }
    }

    // Search functionality
    searchButton.addEventListener("click", function () {
        const query = searchInput.value.trim();
        renderEvents(query);
    });

    searchInput.addEventListener("keyup", function (event) {
        if (event.key === "Enter") {
            renderEvents(this.value.trim());
        }
    });

    // Initial render
    renderEvents();
}





/* 


  NEWS MANAGEMENT


*/





// Function to render news articles
function renderNews(filter = "") {
    document.getElementById("news-items").innerHTML = ""; // Clear existing news

    let filteredNews = newsArticles.filter(article => 
        article.title.toLowerCase().includes(filter.toLowerCase())
    );

    filteredNews.forEach(article => {
        const row = document.createElement("tr");
        row.dataset.id = article.id;
        let status = event.availableAt?(Date.now() - (new Date(event.availableAt)).getTime() ) - 5000 > 0?'upcoming':'published':'draft';
        row.innerHTML = `
            <td><div class="drag-handle"><i class="fas fa-grip-lines"></i></div></td>
            <td>${article.title}</td>
            <td>${article.category}</td>
            <td>${article.date}</td>
            <td><span class="status ${status}">${status}</span></td>
            <td>
                <button class="btn icon edit-btn" data-bs-toggle="modal" data-bs-target="#modalContent" data-id="${article.id}"><i class="fas fa-edit"></i></button>
                <button class="btn icon danger delete-btn" data-id="${article.id}"><i class="fas fa-trash"></i></button>
            </td>
        `;
        document.getElementById("news-items").appendChild(row);
    });

    attachEventListeners2();
}

// Function to attach event listeners to edit and delete buttons
function attachEventListeners2() {
    document.querySelectorAll("#news-items .delete-btn").forEach(button => {
      button.addEventListener("click", function () {
        const articleId = parseInt(this.dataset.id);
        deleteNews(articleId);
      });
    });
    
    document.querySelectorAll("#news-items .edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            const articleId = parseInt(this.dataset.id);
            editNews(articleId);
        });
    });
}

// Function to delete a news article
  function deleteNews(id) {
    request({
      url: requestUrl,
      method: 'POST',
      data: {
        deleteContent: true,
        param: {
          id: id
        }
      },
      success: (data)=>{
        if(data.error){
          alert(data.error.message);
          return;
        }

        newsArticles = newsArticles.filter(article => article.id !== id);
        renderNews();
        FetchNews();
      }
    })
  }

  // Function to edit a news article (This can be expanded with a modal)
  function editNews(id) {
      const article = newsArticles.find(article => article.id == id);
      // console.log('edit news', article)
      if (article) {
        idofAction = id;
        typeofAction = 'edit'
        document.querySelector('#inputContentTitle').value = article.title;
        document.querySelector('#inputContentavailable').value = article.availableAt;
        document.querySelector('#inputContentexpire').value = article.expireAt;
        document.querySelector('#inputContentCategory').value = article.category;
        document.querySelector('#inputContentCategory').value = article.description;
        
      }
  }


function initnewsmanagement(){
  const newsContainer = document.getElementById("news-items");
  const addNewsButton = document.getElementById("add-news");
  const searchInput = document.getElementById("search-news");
  const searchButton = document.getElementById("search-button");

  // Sample news data (can be fetched from an API)


  // Function to add a new news article
  addNewsButton.addEventListener('click', ()=>{
    typeofAction = 'add';
    idofAction = null
  })
  document.querySelector('#contentModalForm').addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = serializeForm(e.target).object;
    const id = idofAction;
    const data = typeofAction=='add'?{addContent: true, param: formData}:{editContent: true, param: {param: formData, condition:{id: id}}}
    console.log('formdata', data)

    request({
      url: 'server/request.php',
      method: 'POST',
      data: data,
      success: (data)=>{
        if (data.error) {
          alert(data.error.message)
          return
        }

        FetchNews();
      }
    })
    return
    

    {
      const newId = newsArticles.length + 1;
      const newArticle = {
          id: newId,
          title: `New Article ${newId}`,
          category: "General",
          date: new Date().toISOString().split("T")[0], // Today's date
          status: "Draft"
      };
      newsArticles.push(newArticle);
      renderNews();
    }


  });

  // Search functionality
  searchButton.addEventListener("click", function () {
      const query = searchInput.value.trim();
      renderNews(query);
  });

  searchInput.addEventListener("keyup", function (event) {
      if (event.key === "Enter") {
          renderNews(this.value.trim());
      }
  });

  // Initial render
  renderNews();
}




















function initslidermanagement(){
const sliderContainer = document.getElementById("slider-items");
    const addSliderButton = document.getElementById("add-slider");

    // Sample slider data (This can be fetched from a backend API)


    // Function to render sliders dynamically
    function renderSliders() {
        sliderContainer.innerHTML = ""; // Clear existing sliders
        sliders.forEach(slider => {
            const sliderElement = document.createElement("div");
            sliderElement.classList.add("slider-item");
            sliderElement.dataset.id = slider.id;
            sliderElement.innerHTML = `
                <div class="drag-handle"><i class="fas fa-grip-lines"></i></div>
                <div class="slider-preview">
                    <img src="${slider.img}" alt="${slider.title}">
                </div>
                <div class="slider-info">
                    <h3>${slider.title}</h3>
                    <p>${slider.description}</p>
                    <div class="slider-actions">
                        <button class="btn small edit-btn" data-id="${slider.id}"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn small danger delete-btn" data-id="${slider.id}"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            `;
            sliderContainer.appendChild(sliderElement);
        });

        attachEventListeners();
    }

    // Function to attach event listeners to edit and delete buttons
    function attachEventListeners() {
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function () {
                const sliderId = parseInt(this.dataset.id);
                deleteSlider(sliderId);
            });
        });

        document.querySelectorAll(".edit-btn").forEach(button => {
            button.addEventListener("click", function () {
                const sliderId = parseInt(this.dataset.id);
                editSlider(sliderId);
            });
        });
    }

    // Function to add a new slider
    addSliderButton.addEventListener("click", function () {
        const newId = sliders.length + 1;
        const newSlider = {
            id: newId,
            title: `New Slider ${newId}`,
            description: "Newly added slider",
            img: "/placeholder.svg?height=150&width=300"
        };
        sliders.push(newSlider);
        renderSliders();
    });

    // Function to delete a slider
    function deleteSlider(id) {
        sliders = sliders.filter(slider => slider.id !== id);
        renderSliders();
    }

    // Function to edit a slider (This can be expanded to open an edit modal)
    function editSlider(id) {
        const slider = sliders.find(slider => slider.id === id);
        if (slider) {
            const newTitle = prompt("Enter new title:", slider.title);
            if (newTitle) {
                slider.title = newTitle;
                renderSliders();
            }
        }
    }

    // Initial render
    renderSliders();

  }






















// Initialize sidebar toggle functionality
function initSidebar() {
  const sidebarToggle = document.getElementById("sidebar-toggle")
  const sidebar = document.querySelector(".sidebar")
  const content = document.querySelector(".content")

  if (sidebarToggle && sidebar && content) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active")
      content.classList.toggle("full-width")
    })
  }
}

// Initialize navigation between sections
function initNavigation() {
  const navLinks = document.querySelectorAll(".sidebar-nav ul li a")
  const sections = document.querySelectorAll(".content-section")

  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      if (this.getAttribute("href").startsWith("#")) {
        e.preventDefault()

        // Remove active class from all links and sections
        navLinks.forEach((link) => link.parentElement.classList.remove("active"))
        sections.forEach((section) => section.classList.remove("active"))

        // Add active class to clicked link
        this.parentElement.classList.add("active")

        // Show corresponding section
        const targetId = this.getAttribute("href").substring(1)
        const targetSection = document.getElementById(targetId)
        if (targetSection) {
          targetSection.classList.add("active")
        }
      }
    })
  })
}



























// Initialize menu management functionality
function initMenuManagement() {
  const menuTree = document.getElementById("menu-tree")
  const menuItemForm = document.getElementById("menu-item-form")
  const addMenuItemBtn = document.getElementById("add-menu-item")
  const saveMenuBtn = document.getElementById("save-menu")
  const deleteMenuItemBtn = document.getElementById("delete-menu-item")
  const cancelEditBtn = document.getElementById("cancel-edit")
  const menuParentSelect = document.getElementById("menu-parent")

  if (!menuTree || !menuItemForm) return

  // Load menu items from localStorage or use default
  let menuItems = loadMenuItems()

  // Render menu tree
  renderMenuTree()

  // Add new menu item
  if (addMenuItemBtn) {
    addMenuItemBtn.addEventListener("click", () => {
      // Reset form
      menuItemForm.reset()
      menuItemForm.dataset.mode = "add"
      menuItemForm.dataset.itemId = ""

      // Update parent options
      updateParentOptions()

      // Show form
      menuItemForm.classList.add("active")
    })
  }

  // Save menu item
  if (menuItemForm) {

    menuItemForm.addEventListener("submit", function (e) {
      e.preventDefault()

      const mode = this.dataset.mode
      const itemId = this.dataset.itemId

      const formData = {
        title: document.getElementById("menu-title").value,
        url: document.getElementById("menu-url").value,
        parent: document.getElementById("menu-parent").value
          ? Number.parseInt(document.getElementById("menu-parent").value)
          : null,
        order: Number.parseInt(document.getElementById("menu-order").value),
      }

      if (mode === "add") {
        // Add new item
        const newItem = {
          id: Date.now(), // Use timestamp as ID
          ...formData,
        }

        menuItems.push(newItem)
      } else if (mode === "edit") {
        // Update existing item
        const index = menuItems.findIndex((item) => item.id === Number.parseInt(itemId))
        if (index !== -1) {
          console.log("this is index = -1 ")
          
          menuItems[index] = {
            ...menuItems[index],
            ...formData,
          }
        }
      }
      
      // Save to localStorage
      if(formData.title==='') return
      saveMenuItems()

      // Re-render menu tree
      renderMenuTree()

      // Reset form
      menuItemForm.reset()
      menuItemForm.classList.remove("active")
    })
  }

  // Delete menu item
  if (deleteMenuItemBtn) {
    deleteMenuItemBtn.addEventListener("click", () => {
      const itemId = menuItemForm.dataset.itemId
      if (!itemId) return

      if (confirm("Are you sure you want to delete this menu item?")) {
        // Remove item and its children
        removeMenuItem(Number.parseInt(itemId))

        // Save to localStorage
        saveMenuItems()

        // Re-render menu tree
        renderMenuTree()

        // Reset form
        menuItemForm.reset()
        menuItemForm.classList.remove("active")
      }
    })
  }

  // Cancel edit
  if (cancelEditBtn) {
    cancelEditBtn.addEventListener("click", (e) => {
      e.preventDefault()
      menuItemForm.reset()
      menuItemForm.classList.remove("active")
    })
  }

  // Save entire menu structure
  if (saveMenuBtn) {
    saveMenuBtn.addEventListener("click", () => {
      // Save to localStorage
      saveMenuItems()

      // Show success message
      alert("Menu structure saved successfully!")
    })
  }

  // Helper function to load menu items
  function loadMenuItems() {
    const storedItems = localStorage.getItem("menuItems")
    if (storedItems) {
      return JSON.parse(storedItems)
    } else {
      // Default menu items
      return [
        { id: 1, title: "Home", url: "index.html", parent: null, order: 0 },
        { id: 2, title: "About", url: "#", parent: null, order: 1 },
        { id: 3, title: "Academics", url: "#", parent: null, order: 2 },
        { id: 4, title: "Admissions", url: "#", parent: null, order: 3 },
        { id: 5, title: "Campus Life", url: "#", parent: null, order: 4 },
        { id: 6, title: "History", url: "#", parent: 2, order: 0 },
        { id: 7, title: "Mission & Vision", url: "#", parent: 2, order: 1 },
        { id: 8, title: "Leadership", url: "#", parent: 2, order: 2 },
        { id: 9, title: "Undergraduate", url: "#", parent: 3, order: 0 },
        { id: 10, title: "Graduate", url: "#", parent: 3, order: 1 },
        { id: 11, title: "Departments", url: "#", parent: 3, order: 2 },
        { id: 12, title: "Engineering", url: "#", parent: 11, order: 0 },
        { id: 13, title: "Business", url: "#", parent: 11, order: 1 },
        { id: 14, title: "Arts & Sciences", url: "#", parent: 11, order: 2 },
      ]
    }
  }

  // Helper function to save menu items
  function saveMenuItems() {

    localStorage.setItem("menuItems", JSON.stringify(menuItems))
  }

  // Helper function to render menu tree
  function renderMenuTree() {
    if (!menuTree) return

    // Get top-level items
    const topLevelItems = menuItems.filter((item) => item.parent === null).sort((a, b) => a.order - b.order)

    let treeHTML = ""

    topLevelItems.forEach((item) => {
      treeHTML += buildMenuItemHTML(item)
    })

    menuTree.innerHTML = treeHTML

    // Add click event to edit menu items
    const menuItemElements = menuTree.querySelectorAll(".menu-item")
    menuItemElements.forEach((element) => {
      element.addEventListener("click", function (e) {
        if (e.target.classList.contains("menu-item") || e.target.classList.contains("menu-item-title")) {
          const itemId = this.dataset.id
          editMenuItem(Number.parseInt(itemId))
        }
      })
    })
  }

  // Helper function to build menu item HTML
  function buildMenuItemHTML(item) {
    const children = menuItems.filter((child) => child.parent === item.id).sort((a, b) => a.order - b.order)

    let itemHTML = `
      <li>
        <div class="menu-item" data-id="${item.id}">
          <span class="menu-item-title">${item.title}</span>
          <div class="menu-item-actions">
            <button class="btn icon small"><i class="fas fa-edit"></i></button>
          </div>
        </div>
    `

    if (children.length > 0) {
      itemHTML += '<ul class="submenu">'
      children.forEach((child) => {
        itemHTML += buildMenuItemHTML(child)
      })
      itemHTML += "</ul>"
    }

    itemHTML += "</li>"

    return itemHTML
  }

  // Helper function to edit menu item
  function editMenuItem(itemId) {
    const item = menuItems.find((item) => item.id === itemId)
    if (!item) return

    // Set form values
    document.getElementById("menu-title").value = item.title
    document.getElementById("menu-url").value = item.url
    document.getElementById("menu-order").value = item.order

    // Update parent options
    updateParentOptions(itemId)

    // Set parent value
    document.getElementById("menu-parent").value = item.parent || ""

    // Set form mode
    menuItemForm.dataset.mode = "edit"
    menuItemForm.dataset.itemId = itemId

    // Show form
    menuItemForm.classList.add("active")
  }

  // Helper function to update parent options
  function updateParentOptions(excludeId = null) {
    if (!menuParentSelect) return

    // Clear existing options except the first one
    while (menuParentSelect.options.length > 1) {
      menuParentSelect.remove(1)
    }

    // Add options for all items except the one being edited and its children
    const validParents = getValidParents(excludeId)
    validParents.forEach((item) => {
      const option = document.createElement("option")
      option.value = item.id
      option.textContent = item.title
      menuParentSelect.appendChild(option)
    })
  }

  // Helper function to get valid parents
  function getValidParents(excludeId = null) {
    if (!excludeId) return menuItems

    // Get all descendants of the item being edited
    const descendants = getDescendants(excludeId)

    // Filter out the item being edited and its descendants
    return menuItems.filter((item) => item.id !== excludeId && !descendants.includes(item.id))
  }

  // Helper function to get all descendants of an item
  function getDescendants(itemId) {
    let descendants = []

    // Get direct children
    const children = menuItems.filter((item) => item.parent === itemId).map((item) => item.id)

    // Add children to descendants
    descendants = descendants.concat(children)

    // Recursively get descendants of children
    children.forEach((childId) => {
      descendants = descendants.concat(getDescendants(childId))
    })

    return descendants
  }

  // Helper function to remove a menu item and its children
  function removeMenuItem(itemId) {
    // Get all descendants
    const descendants = getDescendants(itemId)

    // Remove descendants
    menuItems = menuItems.filter((item) => !descendants.includes(item.id))

    // Remove the item itself
    menuItems = menuItems.filter((item) => item.id !== itemId)

  }
}































// Initialize drag and drop functionality
function initDragAndDrop() {
  // Check if Sortable library is available
  if (typeof Sortable !== "undefined") {
    const menuTree = document.getElementById("menu-tree")
    if (!menuTree) return

    // Initialize sortable for the main menu
    new Sortable(menuTree, {
      group: "nested",
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      onEnd: (evt) => {
        // Update menu structure in localStorage
        updateMenuOrder()
      },
    })

    // Initialize sortable for submenus
    const submenus = document.querySelectorAll(".submenu")
    submenus.forEach((submenu) => {
      new Sortable(submenu, {
        group: "nested",
        animation: 150,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onEnd: (evt) => {
          // Update menu structure in localStorage
          updateMenuOrder()
        },
      })
    })
  } else {
    // Fallback for when Sortable library is not available
    console.log("Sortable library not loaded. Drag and drop functionality disabled.")

    // Add a message to the menu management section
    const menuStructure = document.querySelector(".menu-structure")
    if (menuStructure) {
      const message = document.createElement("div")
      message.className = "alert alert-warning"
      message.textContent =
        "Drag and drop functionality requires the Sortable.js library. Please include it in your project."
      menuStructure.prepend(message)
    }

    // For demo purposes, we'll simulate the drag and drop functionality
    // by adding a note about how it would work
    const helpText = document.querySelector(".menu-structure .help-text")
    if (helpText) {
      helpText.innerHTML +=
        "<br><strong>Note:</strong> In the full implementation, you would be able to drag and drop items to rearrange them."
    }
  }

  // Helper function to update menu order after drag and drop
  function updateMenuOrder() {
    // This function would update the order of menu items in localStorage
    // based on the new DOM structure after drag and drop
    console.log("Menu order updated")
  }
}

document.addEventListener("DOMContentLoaded", () => {
  // Initialize sidebar toggle
  initSidebar()

  // Initialize navigation
  initNavigation()

  // Initialize menu management
  initMenuManagement()

  // Initialize drag and drop functionality for menu
  initMenuDragAndDrop()

  // Initialize drag and drop for sliders
  initSliderDragAndDrop()

  // Initialize drag and drop for news
  initNewsDragAndDrop()

  // Initialize drag and drop for events
  initEventsDragAndDrop()

  // Add Sortable.js library if not already included
  loadSortableLibrary()
})

// Load Sortable.js library if not already included
function loadSortableLibrary() {
  if (typeof Sortable === "undefined") {
    const script = document.createElement("script")
    script.src = "https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
    script.onload = () => {
      console.log("Sortable.js library loaded")
      // Initialize all sortable elements
      initAllSortables()
    }
    document.head.appendChild(script)
  } else {
    // If already loaded, initialize sortables
    initAllSortables()
  }
}

// Initialize all sortable elements after library is loaded
function initAllSortables() {
  initMenuDragAndDrop()
  initSliderDragAndDrop()
  initNewsDragAndDrop()
  initEventsDragAndDrop()
}

// Initialize drag and drop functionality for menu
function initMenuDragAndDrop() {
  const menuTree = document.getElementById("menu-tree")
  if (!menuTree) return

  // Initialize sortable for the main menu
  if (typeof Sortable !== "undefined") {
    new Sortable(menuTree, {
      group: "nested",
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      handle: ".menu-item",
      onEnd: (evt) => {
        // Update menu structure in localStorage
        updateMenuOrder()
      },
    })

    // Initialize sortable for submenus
    const submenus = document.querySelectorAll(".submenu")
    submenus.forEach((submenu) => {
      new Sortable(submenu, {
        group: "nested",
        animation: 150,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        handle: ".menu-item",
        onEnd: (evt) => {
          // Update menu structure in localStorage
          updateMenuOrder()
        },
      })
    })
  }
}

// Initialize drag and drop functionality for sliders
function initSliderDragAndDrop() {
  const sliderItems = document.getElementById("slider-items")
  if (!sliderItems) return

  if (typeof Sortable !== "undefined") {
    new Sortable(sliderItems, {
      animation: 150,
      handle: ".drag-handle",
      ghostClass: "sortable-ghost",
      chosenClass: "sortable-chosen",
      onEnd: (evt) => {
        // Save the new order to localStorage
        saveSliderOrder()
      },
    })
  }
}

// Initialize drag and drop functionality for news
function initNewsDragAndDrop() {
  const newsItems = document.getElementById("news-items")
  if (!newsItems) return

  if (typeof Sortable !== "undefined") {
    new Sortable(newsItems, {
      animation: 150,
      handle: ".drag-handle",
      ghostClass: "sortable-ghost",
      chosenClass: "sortable-chosen",
      onEnd: (evt) => {
        // Save the new order to localStorage
        saveNewsOrder()
      },
    })
  }
}

// Initialize drag and drop functionality for events
function initEventsDragAndDrop() {
  const eventItems = document.getElementById("event-items")
  if (!eventItems) return

  if (typeof Sortable !== "undefined") {
    new Sortable(eventItems, {
      animation: 150,
      handle: ".drag-handle",
      ghostClass: "sortable-ghost",
      chosenClass: "sortable-chosen",
      onEnd: (evt) => {
        // Save the new order to localStorage
        saveEventsOrder()
      },
    })
  }
}

// Helper function to save slider order
function saveSliderOrder() {
  const sliderItems = document.getElementById("slider-items")
  if (!sliderItems) return

  const sliderOrder = []
  const items = sliderItems.querySelectorAll(".slider-item")

  items.forEach((item, index) => {
    const id = item.dataset.id
    sliderOrder.push({
      id: Number.parseInt(id),
      order: index,
    })
  })
 console.log(sliderOrder)
  // Save to localStorage
  localStorage.setItem("sliderOrder", JSON.stringify(sliderOrder))

  // Show a notification
  showNotification("Slider order updated successfully")
}

// Helper function to save news order
function saveNewsOrder() {
  const newsItems = document.getElementById("news-items")
  if (!newsItems) return

  const newsOrder = []
  const items = newsItems.querySelectorAll("tr")

  items.forEach((item, index) => {
    const id = item.dataset.id
    newsOrder.push({
      id: Number.parseInt(id),
      order: index,
    })
  })
  console.log(newsOrder)
  // Save to localStorage
  localStorage.setItem("newsOrder", JSON.stringify(newsOrder))

  // Show a notification
  showNotification("News order updated successfully")
}

// Helper function to save events order
function saveEventsOrder() {
  const eventItems = document.getElementById("event-items")
  if (!eventItems) return

  const eventsOrder = []
  const items = eventItems.querySelectorAll("tr")

  items.forEach((item, index) => {
    const id = item.dataset.id
    eventsOrder.push({
      id: Number.parseInt(id),
      order: index,
    })
  })

  // Save to localStorage
  localStorage.setItem("eventsOrder", JSON.stringify(eventsOrder))

  // Show a notification
  showNotification("Events order updated successfully")
}

// Helper function to show a notification
function showNotification(message) {
  // Check if notification container exists, if not create it
  let notificationContainer = document.querySelector(".notification-container")

  if (!notificationContainer) {
    notificationContainer = document.createElement("div")
    notificationContainer.className = "notification-container"
    document.body.appendChild(notificationContainer)
  }

  // Create notification element
  const notification = document.createElement("div")
  notification.className = "notification"
  notification.innerHTML = `
    <div class="notification-content">
      <i class="fas fa-check-circle"></i>
      <span>${message}</span>
    </div>
  `

  // Add to container
  notificationContainer.appendChild(notification)

  // Remove after 3 seconds
  setTimeout(() => {
    notification.classList.add("fade-out")
    setTimeout(() => {
      notification.remove()
    }, 300)
  }, 3000)
}

// Helper function to update menu order after drag and drop
function updateMenuOrder() {
  // This function would update the order of menu items in localStorage
  // based on the new DOM structure after drag and drop
  console.log("Menu order updated")

  // Show a notification
  showNotification("Menu order updated successfully")
}


document.addEventListener("DOMContentLoaded", () => {
  // Initialize sidebar toggle
  initSidebar()

  // Initialize navigation
  initNavigation()

  // Initialize menu management
  initMenuManagement()
  initslidermanagement()
  
  initnewsmanagement()
  initeventmanagement()
  FetchNews();
  // Initialize drag and drop functionality
  initDragAndDrop()
  document.getElementById("news-count").textContent = newsArticles.length;
    document.getElementById("slider-count").textContent = sliders.length;
    document.getElementById("event-count").textContent = events.length;

  document.querySelector('#getNews').addEventListener('click', ()=>{
    FetchNews();
  })

})