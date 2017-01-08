HeroManager = {	
	CreateUser: function(userName, userEmail, userPassword, callback, context)
	{
		$.post
		(
			"http://timfalken.com/heromanager/createuser",
			'{"name":"' + userName + '", "email":"' + userEmail + '", "password":"' + userPassword + '"}',
			function(data) 
			{
			  console.log(data);

				callback && callback.call(context, data);
			}
		);
	},
	CreateHero: function(userId, name, classId, key, callback, context)
	{
		$.post
		(
			"http://timfalken.com/heromanager/createhero",
			'{"ownerId":"' + userId + '", "name":"' + name + '", "classId":"' + classId + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);

				callback && callback.call(context, data);
			}
		);
	},
	GetUserKey: function(userEmail, userPassword, callback, context)
	{
		$.post
		(
			"http://timfalken.com/heromanager/getkey",
			'{"email":"' + userEmail + '", "password":"' + userPassword + '"}',
			function(data) 
			{
			  console.log(data);

				callback && callback.call(context, data);
			}
		);
	},
	CommandHero: function(heroId, action, key, callback, context)
	{
		$.post
		(
			"http://timfalken.com/heromanager/command",
			'{"id":' + heroId + ', "action":"' + action + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);

				callback && callback.call(context, data);
			}
		);
	},

	SellHeroItem: function(heroId, itemId, key, callback, context)
	{
		$.post
		(
			"http://timfalken.com/heromanager/shop",
			'{"id":' + heroId + ', "action":"Selling", "itemId":"' + itemId + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);

				callback && callback.call(context, data);
			}
		);
	},

	BuyShopItem: function(heroId, shopItemId, key, callback, context)
	{
		$.post
		(
			"http://timfalken.com/heromanager/shop",
			'{"id":' + heroId + ', "action":"Buying", "itemId":"' + shopItemId + '", "key":"' + key + '"}',
			function(data) 
			{
			  console.log(data);

				callback && callback.call(context, data);
			}
		);
	}
}