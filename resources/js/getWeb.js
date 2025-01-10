// const puppeteer = require('puppeteer');
import puppeteer from 'puppeteer';

(async () => {
  const [dairiten_cd, password, month] = process.argv.slice(2);
  
  const browser = await puppeteer.launch({ headless: true });
  const page = await browser.newPage();

  // ログインページにアクセス
  await page.goto('');

  // _tokenの値を取得
  const token = await page.$eval('input[name="_token"]', el => el.value);

  // ログインフォームを埋める
  await page.type('input[name="dairiten_cd"]', dairiten_cd);
  await page.type('input[name="password"]', password);
  await page.evaluate((token) => {
    document.querySelector('input[name="_token"]').value = token;
  }, token);

  // フォームを送信
  await Promise.all([
    page.click('button[type="submit"]'), // ログインボタンのセレクタを指定
    page.waitForNavigation(), // ナビゲーションを待つ
  ]);

  // ログイン成功後、クッキーを保持
  const cookies = await page.cookies();

  // ログイン後のページにアクセス
  await page.goto('');

  // ドロップダウンリストから特定の月を選択
  await page.select('select[name="nentuki"]', month);

  // フォームを送信
  await page.evaluate(() => {
    document.querySelector('select[name="nentuki"]').form.submit();
  });

  // ページのナビゲーションを待つ
  await page.waitForNavigation();

  // 必要なデータを取得
  const data = await page.evaluate(() => {
    return document.querySelector('table').innerText;
  });

  console.log(data); // 取得したデータをコンソールに出力

  await browser.close();
})();
