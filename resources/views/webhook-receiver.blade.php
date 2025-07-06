<form method="POST" action="{{ route('checkout') }}">
    @csrf
    <div>
        <label for="total">total:</label>
        <input type="text" name="total" id="total">
    </div>
    <div>
        <label for="quantity">quantity:</label>
        <input type="text" name="quantity" id="quantity">
    </div>
    <div>
        <label for="description">description:</label>
        <input type="text" name="description" id="description">
    </div>
    <div>
        <button type="submit">Checkout</button>
    </div>
</form>
