HeroManager = {	
	CreateUser: function(userName, userEmail, userPassword)
	{
		$.post
		(
			"http://timfalken.com/heromanager/createuser",
			'{"name":"' + userName + '", "email":"' + userEmail + '", "password":"' + userPassword + '"}',
			function(data) 
			{
			  console.log(data);
			}
		);
	},
	CreateHero: function(userId, name, classId, key)
	{
		$.post
		(
			"http://timfalken.com/heromanager/createhero",
			'{"ownerId":"' + userId + '", "name":"' + name + '", "classId":"' + classId + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);
			}
		);
	},
	GetUserKey: function(userEmail, userPassword)
	{
		$.post
		(
			"http://timfalken.com/heromanager/getkey",
			'{"email":"' + userEmail + '", "password":"' + userPassword + '"}',
			function(data) 
			{
			  console.log(data);
			}
		);
	},
	CommandHero: function(heroId, action, key)
	{
		$.post
		(
			"http://timfalken.com/heromanager/command",
			'{"id":' + heroId + ', "action":"' + action + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);
			}
		);
	},

	SellHeroItem: function(heroId, itemId, key)
	{
		$.post
		(
			"http://timfalken.com/heromanager/shop",
			'{"id":' + heroId + ', "action":"Selling", "itemId":"' + itemId + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);
			}
		);
	},

	BuyShopItem: function(heroId, shopItemId, key)
	{
		$.post
		(
			"http://timfalken.com/heromanager/shop",
			'{"id":' + heroId + ', "action":"Buying", "itemId":"' + shopItemId + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);
			}
		);
	}
}