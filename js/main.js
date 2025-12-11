document.addEventListener('DOMContentLoaded', function () {
    // Attach search event listeners after content is loaded
    attachSearchEventListeners();

    // Add click event listener to the search icon (for both desktop and mobile)
    const searchIcon = document.getElementById("search-icon");
    const searchIconMobile = document.getElementById("search-icon-mobile");

    if (searchIcon) {
        searchIcon.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default behavior
            redirectToSearch();
        });
    }

    if (searchIconMobile) {
        searchIconMobile.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default behavior
            redirectToSearch();
        });
    }

    // JS Script for Remember Me function "Local Storage"
    const savedEmail = localStorage.getItem("savedEmail");
    
    if (savedEmail) {
        document.getElementById("email").value = savedEmail;
        document.getElementById("rememberMe").checked = true;
    }

    const loginForm = document.querySelector("form[action*='process_login.php']");

    if (loginForm) {
        loginForm.addEventListener("submit", function () {
        const email = document.getElementById("email").value;
        const remember = document.getElementById("rememberMe").checked;

        if (remember) {
            localStorage.setItem("savedEmail", email);
        } else {
            localStorage.removeItem("savedEmail");
        }
        });
    }

});

// Function to attach search event listeners
function attachSearchEventListeners() {
    // Event listener for search input on large screen
    const searchInput = document.getElementById("search-input");
    const searchInputMobile = document.getElementById("search-input-mobile");

    if (searchInput) {
        searchInput.addEventListener("input", function(event) {
            event.preventDefault(); // Prevent form submission
            updatePosts(this.value);
        });
    }

    if (searchInputMobile) {
        searchInputMobile.addEventListener("input", function(event) {
            event.preventDefault(); // Prevent form submission
            updatePosts(this.value);
        });
    }
}

// Function to update posts based on search query
function updatePosts(query) {
    const postsContainer = document.getElementById("posts-container");

    if (!postsContainer) {
        console.error("Posts container not found!");
        return;
    }

    // Temporarily show the "Loading..." state in the posts container
    postsContainer.innerHTML = "<p>Loading...</p>";
    postsContainer.style.display = "none";  // Hide the container during the AJAX call

    // If the search bar is cleared, request all posts
    if (query.trim() === "") {
        // Just reload the page without using AJAX, PHP will handle all posts
        window.location.href = window.location.pathname; // Reload page without search query
        return;  // Exit the function to prevent AJAX for empty query
    }

    // Create a new XMLHttpRequest object
    let xhr = new XMLHttpRequest();
    
    // Prepare the request to send to home_loggedin.php with the query as a parameter
    xhr.open('GET', 'home_loggedin.php?query=' + encodeURIComponent(query) + '&ajax=true', true);

    // Set the function to execute when the response is received
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Create a temporary container to hold the new content
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = xhr.responseText;
            
            // Clear existing content and append new content
            postsContainer.innerHTML = '';
            Array.from(tempDiv.children).forEach(child => {
                postsContainer.appendChild(child);
            });
            
            // Force the container to stay at the top
            postsContainer.style.marginTop = '0';
            postsContainer.style.paddingTop = '0';
        }
    };

    // Send the request
    xhr.send();
}

// Function to redirect to home_loggedin.php with the search query
function redirectToSearch() {
    // Get the search query from the input field (for both desktop and mobile)
    const searchQuery = document.getElementById("search-input")?.value || document.getElementById("search-input-mobile")?.value;

    // Redirect to home_loggedin.php with the search query as a URL parameter
    if (searchQuery) {
        window.location.href = `home_loggedin.php?query=${encodeURIComponent(searchQuery)}`;
    } else {
        // If no query, just redirect to home_loggedin.php
        window.location.href = "home_loggedin.php";
    }
}