<?php
// ==========================================
// DB Connection
// ==========================================
$conn = new mysqli('localhost', 'root', '', 'wasabi_kitchen');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ==========================================
// HTML UI Section
// ==========================================
if (!isset($_GET['products']) && !isset($_GET['orders']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Wasabi Kitchen</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white px-[100px]">
  <h1 class="mt-20 mb-16 text-3xl font-extrabold md:text-5xl lg:text-6xl text-center">
    <span class="text-transparent bg-clip-text bg-gradient-to-r to-emerald-600 from-sky-400">Wasabi Kitchen</span>
  </h1>

  <div class="text-right mb-8">
    <button onclick="checkPermission()" class="bg-blue-600 text-white px-4 py-2 rounded">Add Product</button>
  </div>

  <!-- Modal -->
  <div id="product-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow p-6 text-black w-full max-w-md relative">
      <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600" onclick="closeModal()">&times;</button>
      <h3 class="text-xl font-bold mb-4">Add Product</h3>
      <form id="addProductForm" class="space-y-3">
        <input type="text" name="name" placeholder="Product name" required class="w-full border p-2 rounded text-black" />
        <input type="number" name="price" placeholder="Price" required step="0.01" class="w-full border p-2 rounded text-black" />
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Add</button>
      </form>
    </div>
  </div>

  <div class="lg:grid lg:grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Create Order -->
    <section class="form mb-10" id="CreateOrder">
      <h2 class="text-xl font-bold mb-4">Create Order</h2>
      <form>
        <input type="text" name="customer" placeholder="Customer Name" required class="mb-4 p-2 w-full rounded text-black">
        <ul class="space-y-2"></ul>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 mt-4 rounded w-full">Place Order</button>
      </form>
    </section>

    <!-- Order Report -->
    <section id="OrderReport" class="col-span-2 mb-10">
      <h2 class="text-xl font-bold mb-4">Order Report</h2>
      <table class="min-w-full text-left">
        <thead class="bg-gray-700">
          <tr>
            <th class="px-6 py-3">Customer</th>
            <th class="px-6 py-3">Products</th>
            <th class="px-6 py-3">Total</th>
            <th class="px-6 py-3">Status</th>
          </tr>
        </thead>
        <tbody class="bg-gray-800"></tbody>
      </table>
    </section>
  </div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const selected = [];
  const addForm = document.querySelector('#addProductForm');
  const orderForm = document.querySelector('section.form form');
  const productUL = document.querySelector('#CreateOrder ul');
  const orderTbody = document.querySelector('#OrderReport tbody');

  async function loadProducts() {
    const res = await fetch('api.php?products=1');
    const list = await res.json();
    productUL.innerHTML = '';
    list.forEach(p => {
      productUL.insertAdjacentHTML('beforeend', `
        <li class="py-2 border-b flex justify-between items-center">
          <div><strong>${p.name}</strong> <span class="text-gray-400">BDT ${p.price}</span></div>
          <button data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" class="btn-select bg-blue-600 text-white px-3 py-1 rounded">+</button>
        </li>`);
    });

    document.querySelectorAll('.btn-select').forEach(btn => {
      btn.onclick = () => {
        const id = btn.dataset.id;
        const index = selected.findIndex(item => item.id === id);
        if (index === -1) {
          selected.push({ id, name: btn.dataset.name, price: parseFloat(btn.dataset.price) });
          btn.textContent = '-';
          btn.classList.replace('bg-blue-600', 'bg-red-600');
        } else {
          selected.splice(index, 1);
          btn.textContent = '+';
          btn.classList.replace('bg-red-600', 'bg-blue-600');
        }
      };
    });
  }

  async function loadOrders() {
    const res = await fetch('api.php?orders=1');
    const list = await res.json();
    orderTbody.innerHTML = '';
    list.forEach(o => {
      const names = o.food_names.join(', ');
      const statusBtn = o.delivered == 1
        ? '<button disabled class="bg-gray-600 text-white px-4 py-1 rounded">DELIVERED</button>'
        : `<button data-id="${o.id}" class="btn-deliver bg-blue-600 text-white px-4 py-1 rounded">DELIVER</button>`;
      const deleteBtn = `<button data-id="${o.id}" class="btn-delete bg-red-600 text-white px-4 py-1 rounded ml-2">DELETE</button>`;

      orderTbody.insertAdjacentHTML('beforeend', `
        <tr class="border-b">
          <td class="px-6 py-2">${o.customer_name}</td>
          <td class="px-6 py-2">${names}</td>
          <td class="px-6 py-2">BDT ${parseFloat(o.total_price).toFixed(2)}</td>
          <td class="px-6 py-2 flex">${statusBtn} ${deleteBtn}</td>
        </tr>`);
    });

    document.querySelectorAll('.btn-deliver').forEach(btn => btn.onclick = async () => {
      await fetch('api.php', {
        method: 'PUT',
        body: `order_id=${btn.dataset.id}`
      });
      loadOrders();
    });

    document.querySelectorAll('.btn-delete').forEach(btn => btn.onclick = async () => {
      if (!confirm("Are you sure you want to delete this order?")) return;
      await fetch('api.php', {
        method: 'DELETE',
        body: `order_id=${btn.dataset.id}`
      });
      loadOrders();
    });
  }

  addForm.onsubmit = async e => {
    e.preventDefault();
    const name = addForm.name.value.trim();
    const price = parseFloat(addForm.price.value);
    if (!name || isNaN(price)) return;
    await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ addProduct: 1, name, price })
    });
    addForm.reset();
    closeModal();
    loadProducts();
  };

  orderForm.onsubmit = async e => {
    e.preventDefault();
    const customer = orderForm.customer.value.trim();
    if (!customer || selected.length === 0) return alert("Provide name and at least one product");
    const total = selected.reduce((acc, item) => acc + item.price, 0);
    await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ createOrder: 1, customer, foods: selected.map(i => i.name), total })
    });
    orderForm.reset();
    selected.length = 0;
    loadProducts();
    loadOrders();
  };

  loadProducts();
  loadOrders();
});

function checkPermission() {
  const pass = prompt("Enter admin password:");
  if (!pass) return;

  fetch('api.php?checkPassword=1', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ password: pass })
  }).then(res => res.json()).then(result => {
    if (result.success) {
      document.getElementById('product-modal').classList.remove('hidden');
    } else {
      alert("Incorrect password!");
    }
  });
}

function closeModal() {
  document.getElementById('product-modal').classList.add('hidden');
}
</script>
</body>
</html>
<?php exit; }

// ==========================================
// API SECTION
// ==========================================
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET' && isset($_GET['products'])) {
  $res = $conn->query("SELECT * FROM products");
  echo json_encode($res->fetch_all(MYSQLI_ASSOC));
  exit;
}

if ($method == 'GET' && isset($_GET['orders'])) {
  $res = $conn->query("SELECT * FROM orders");
  $data = [];
  while ($row = $res->fetch_assoc()) {
    $row['food_names'] = json_decode($row['food_names']);
    $row['delivered'] = (int)$row['delivered'];
    $data[] = $row;
  }
  echo json_encode($data);
  exit;
}

if ($method == 'POST' && isset($_GET['checkPassword'])) {
  $input = json_decode(file_get_contents('php://input'), true);
  $password = $conn->real_escape_string($input['password']);
  $res = $conn->query("SELECT * FROM admin_passwords WHERE password = '$password'");
  echo json_encode(['success' => $res->num_rows > 0]);
  exit;
}

if ($method == 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  if (isset($input['addProduct'])) {
    $stmt = $conn->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
    $stmt->bind_param("sd", $input['name'], $input['price']);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
    exit;
  }
  if (isset($input['createOrder'])) {
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, food_names, total_price) VALUES (?, ?, ?)");
    $foods = json_encode($input['foods']);
    $stmt->bind_param("ssd", $input['customer'], $foods, $input['total']);
    $stmt->execute();
    echo json_encode(['status' => 'order placed']);
    exit;
  }
}

if ($method == 'PUT') {
  parse_str(file_get_contents('php://input'), $put);
  $orderId = intval($put['order_id']);
  $conn->query("UPDATE orders SET delivered = 1 WHERE id = $orderId");
  echo json_encode(['status' => 'delivered']);
  exit;
}

if ($method == 'DELETE') {
  parse_str(file_get_contents('php://input'), $delete);
  $orderId = intval($delete['order_id']);
  $conn->query("DELETE FROM orders WHERE id = $orderId");
  echo json_encode(['status' => 'deleted']);
  exit;
}
?>
