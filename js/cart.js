const { createApp } = Vue;

createApp({
  data() {
    return {
      cart: JSON.parse(localStorage.getItem('cart')) || [],
      showCart: false,
      showCheckout: false,
      step: 1,
      user: { name: '', phone: '' },
      delivery: { method: '', address: '' },
      payment: ''
    };
  },

  computed: {
    totalPrice() {
      return this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    },
    totalQuantity() {
      return this.cart.reduce((sum, item) => sum + item.quantity, 0);
    }
  },

  watch: {
    cart: {
      handler(newCart) {
        localStorage.setItem('cart', JSON.stringify(newCart));
      },
      deep: true
    },
    showCart(val) {
      document.body.classList.toggle('no-scroll', val || this.showCheckout);
    },
    showCheckout(val) {
      document.body.classList.toggle('no-scroll', val || this.showCart);
    }
  },

  methods: {
    toggleCart(product) {
      const index = this.cart.findIndex(item => item.id === product.id);
      if (index !== -1) {
        this.cart.splice(index, 1);
      } else {
        this.cart.push({ ...product, quantity: 1 });
      }
    },
    removeItem(index) {
      this.cart.splice(index, 1);
    },
    increaseQuantity(index) {
      this.cart[index].quantity++;
    },
    decreaseQuantity(index) {
      if (this.cart[index].quantity > 1) {
        this.cart[index].quantity--;
      } else {
        this.removeItem(index);
      }
    },
    isInCart(id) {
      return this.cart.some(item => item.id === id);
    },

    nextStep() {
      if (this.step === 1) {
        if (!this.user.name || !this.user.phone) {
          alert('Будь ласка, заповніть всі поля');
          return;
        }

        // Перевіряємо лише 9 цифр
        if (!/^\d{9}$/.test(this.user.phone)) {
          alert('Невірний номер (має бути 9 цифр після +380)');
          return;
        }
      }

      if (this.step === 2 && this.delivery.method !== 'pickup' && !this.delivery.address) {
        alert('Будь ласка, введіть адресу доставки');
        return;
      }

      if (this.step === 3 && !this.payment) {
        alert('Оберіть спосіб оплати');
        return;
      }

      this.step++;
    },


    prevStep() {
      if (this.step > 1) this.step--;
    },
    submitOrder() {
      const fullPhone = '+380' + this.user.phone;
      console.log('Номер до відправки:', fullPhone);

      alert('Замовлення оформлено!');
      this.step = 4;
    },


    closeCheckout() {
      this.showCheckout = false;
      if (this.step === 4) {
        this.cart = [];
        localStorage.removeItem('cart');
      }
      this.step = 1;
    },
  }
}).mount('#app');
