# Blueskyの自動投稿用のPHPコードです.
```mixed```を戻り値の指定しているのでPHP8と括っていますが、PHP7系でも指定を退ければ使用可能なコードになります.
### 補足
ブルースカイの開発用ドキュメントにハッシュタグの事柄を詳しく書かれてはなかったので補足.
テキストの中にハッシュタグ化する文字を入れ何処から何処までがハッシュタグかを教える必要があります.例えば次のようにテキストを作った場合、ハッシュタグにしたいバイトの位置（起点と終点）を教える感じです.
```こんにちは #挨拶```
### ご利用にあたって
>githubよりクローンするかダウンロードするかでご使用下さい.
>ご自由に使用頂いて構いませんがQiitaかgithubにリンクして頂けたら有り難いです.
>また、いいね👍️、スター★を押していただく励みになります🙇.

```php
//使用例
$response = (new Bluesky(USER_NAME, APP_PASSWORD))->webPost(
                $title,
                $imagePath,
                $link,
                $tags,
            );
$response = (new Bluesky(USER_NAME, APP_PASSWORD))->post(
                $text,
                $imagePath,
                $link,
                $tags,
            );
```
