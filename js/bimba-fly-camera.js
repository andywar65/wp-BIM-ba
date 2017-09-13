var _i = document.querySelector("#bimba-camera");

_i.addEventListener("keydown", function(e)
		{
			if(e.key == "z")
			{
				_i.setAttribute('wasd-controls',{fly: true});
			}
			
			if(e.key == "x")
			{
				_i.setAttribute('wasd-controls',{fly: false});
			}
			
		}, false);