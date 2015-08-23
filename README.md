#Baidu Tieba Helper
---

A simple class of Baidu tieba operations

---

###What can we do?

* __Easy to login via password or only cookie__
* __Post your new topic just needs title and content__
* __Send your comment by topic id__
* __Automatically sign the tieba you liked via mobile API__
* __Return verify code if it is essential__

---

###How should you use it?

####Inlcude it
```php
include 'TiebaHelper.php';
```

####Create a new instance
```php
//Give user name and password when construct
$tb = new TiebaHelper(YOUR_ID, YOUR_PASSWORD);

//or set it later
$tb = new TiebaHelper();
$tb->SetUser(USER_NAME);
$tb->SetPW(USER_PASSWORD);

//Then log in
$tb->Login();
```
####Post a new topic
```php
//First set tieba name
$tb->SetTieba(TIEBA_NAME);

//Then post
$tb->Post(Title, content);
```
####Sent comment
```php
//First set tieba name
$tb->SetTieba(TIEBA_NAME);

//Then set topic id,which can be found in topic Uri
$tb->SetTid(TID);

//Eventually just comment
$tb->Comment(CONTENT);
```
####Sign your tieba
```php
//Simply call Sign method
$tb->Sign();

//Get sign states
$signstates = $tb->GetSignStates();
//Then $signstates is an array in which object is about all information that Baidu server responsed
```

###Other
Contact with me by email [tyanboot@outlook.com](mailto:tyanboot@outlook.com) if you have any question:)