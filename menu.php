<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HIKINIKU</title>
    <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
    <script src="js/script.js"></script>
    <link rel="stylesheet" href="css/style.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@300&display=swap" rel="stylesheet">
    
</head>
<body>
    <div class="container">
        <div id="category-container"></div>
    </div>

    <script id="category-template" type="text/x-handlebars-template">
        <div style="text-align: center; justify-content: center; background-color: #00000000; position:absolute; top:50px;">
        <img src="images/logo/namelogo.png" alt="Main Logo" style="width: 180px; height: 180px; filter: brightness(0.9);">

    </div>
      {{#if name}}
        <h1>{{name}}</h1>
      {{/if}}
      {{#if items.length}}
        <div class="items">
          {{#each items}}
            <div class="item" data-item-id="{{id}}">
              <img src="" alt="" />
              <h4>{{extended_description}}</h4>
              <p>{{price}}</p>
              <div class="controls">
                <button class="decrease">-</button>
                <span class="quantity">0</span>
                <button class="increase">+</button>
              </div>
              <!-- <p class="total">Total: 0</p> -->
            </div>
          {{/each}}
        </div>
      {{/if}}
      <div class="nav-buttons">
        {{#if previous_category_id}}
          <button onclick="navigateCategory('{{previous_category_id}}')">
            Back
          </button>
        {{else}}
          <button onclick="backToIndex()">Back</button>
        {{/if}}
        {{#if next_category_id}}
          <button onclick="navigateCategory('{{next_category_id}}')">
            Next
          </button>
        {{else}}
          <button onclick="addToCart()">View Cart</button>
        {{/if}}
      </div>
    </script>
</body>
</html>