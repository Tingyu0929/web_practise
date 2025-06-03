<script setup>
import { ref } from 'vue';

const outputMessage = ref('點擊按鈕將改變這段文字');
const age = ref(0);
const helloMessage = ref('');
const count = ref(0);

const nameInput = ref('');
const inputName = ref('');

const fruits = ref(['蘋果', '香蕉', '橘子', '葡萄', '草莓']);
const numbers = ref([1, 2, 3, 4, 5]);

const person = ref({
  name: '小明',
  age: 20,
  city: '台北'
});

function changeMessage() {
  outputMessage.value = '你剛剛點了按鈕！';
}
function sayHello() {
  helloMessage.value = '你好，歡迎使用 Vue.js！';
}
function showName() {
  inputName.value = nameInput.value;
  nameInput.value = ''; // 清空輸入框
}
</script>

<template>
  <div class="container">
    <!-- 1. 輸出文字 -->
    <section>
      <h2>1. 輸出文字</h2>
      <p>{{ outputMessage }}</p>
      <button @click="changeMessage">改變文字</button>
    </section>

    <!-- 2. 變數與運算 -->
    <section>
      <h2>2. 變數與運算</h2>
      <p>5 + 3 = {{ 5 + 3 }}</p>
    </section>

    <!-- 3. 條件判斷 -->
    <section>
      <h2>3. 條件判斷</h2>
      <input type="number" v-model.number="age" placeholder="輸入年齡" />
      <p>{{ age >= 18 ? '你是成年人' : '你是未成年人'}}</p>
    </section>

    <!-- 4. 函數 -->
    <section>
      <h2>4. 呼叫函數</h2>
      <button @click="sayHello">呼叫函數</button>
      <p>{{ helloMessage }}</p>
    </section>

    <!-- 5. 陣列 -->
    <section>
      <h2>5. 陣列</h2>
      <ul>
        <li v-for="(fruit, index) in fruits" :key="index">{{ fruit }}</li>
      </ul>
    </section>

    <!-- 6. 迴圈 -->
    <section>
      <h2>6. 迴圈</h2>
      <p>數字陣列 : {{ numbers }}</p>
      <p>總和: {{ numbers.reduce((a, b) => a + b, 0)}}</p>
    </section>

    <!-- 7. 事件處理 -->
    <section>
      <h2>7. 事件處理</h2>
      <button @click="count++">點我 +1</button>
      <p>已點擊 {{ count }} 次</p>
    </section>

    <!-- 8. 物件 -->
    <section>
      <h2>8. 物件</h2>
      <p>{{ person.name }} 是 {{ person.age }} 歲，住在 {{ person.city }}</p>
    </section>

    <!-- 9. DOM 操作 -->
    <section>
      <h2>9. 簡單 DOM 操作</h2>
      <input ref="nameInput" placeholder="輸入你的名字" />
      <button @click="showName">顯示輸入值</button>
      <p>{{ inputName }}</p>
    </section>
  </div>
</template>

<style scoped>
.container {
  max-width: 1280px;
  margin: auto;
  padding: 2rem;
  font-family: Consolas, 'Courier New', monospace;
  font-size: 1.2rem;
  line-height: 1.6;
}

section {
  border-left: 5px solid #42b983;
  background: #f9f9f9;
  padding: 1rem;
  margin-bottom: 1.5rem;
  border-radius: 8px;
}

button {
  margin-top: 0.5rem;
}

input {
  padding: 0.4rem;
  margin-top: 0.5rem;
}
</style>
