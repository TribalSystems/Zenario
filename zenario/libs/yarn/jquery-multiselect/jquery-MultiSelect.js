(function ( $ ) {
 
    $.fn.multiselect = function( options ) {
 
        var settings = $.extend({
            data : [],
            search : false,
            searchPlaceholder : "Search items",
            placeholder : "Select option",
            className : null,
            disable : false,

            onOptionSelect : function(){},
        }, options );

        var MultiSelectData = JSON.parse(JSON.stringify(settings.data));
 

        function toggleDropdown(event){
            $(event.target).closest(".multiselect-container").toggleClass('show');
            if($(event.target).closest(".multiselect-container").hasClass('show')){
                $(event.target).closest(".multiselect-container").find('.search-input').focus();
            }else{
                $(event.target).closest(".multiselect-container").find('.search-input').blur();
            }
        }

        function closeDropdown(){
            $('.multiselect-container .search-input').val("");
            reRenderdropdown(settings.data);
            $(".multiselect-container").removeClass('show');   
            $(event.target).closest(".multiselect-container").removeClass('show');   
            $(event.target).closest(".multiselect-container").find('.search-input').blur();
        }

        function openDropdown(){
            $(event.target).closest(".multiselect-container").addClass('show');      
            $(event.target).closest(".multiselect-container").find('.search-input').focus();
        }

        function reRenderdropdown(data,event){
            if(event){
                var dropdownList = $(event.target).closest('.multiselect-container').find('.items-container');    
            }else{
                var dropdownList = $('.multiselect-container').find('.items-container');    
            }
            dropdownList.empty();

            if(data.length > 0){
                $.each(data, function(index,obj){
                    $('<div />', {
                        "class": 'item',
                        "data-id": index,
                        text: obj.name,
                        click: function(e){
                            closeDropdown(e);
                            if(settings.onOptionSelect && typeof settings.onOptionSelect === "function"){
                                settings.onOptionSelect(obj.name);
                            }
                        }
                    }).appendTo(dropdownList);    
                });    
            }else{
                $('<div />', {
                    "class": 'no-item',
                    text: "No item found",
                    click: function(e){
                        closeDropdown(e);
                    }
                }).appendTo(dropdownList);  
            }
        }

        var conainerClass;
        if(settings.className){
            conainerClass = 'multiselect-container'+" "+settings.className;
        }else{
            conainerClass = 'multiselect-container'   
        }

        if(settings.disable){
            conainerClass = conainerClass+ " " + "disable"
        }

        var wrapper = this.addClass(conainerClass);

        var header = $('<div />', {
            "class": 'header',
            click: function(e){
                toggleDropdown(e)
            }
        }).appendTo(wrapper);

        var selecteditem = $('<div />', {
            "class": 'selected-text',
            text: settings.placeholder
        }).appendTo(header);

        var dropdown = $('<div />', {
            "class": 'dropdown'
        }).appendTo(wrapper);

        if(settings.search){
            var search = $('<div />', {
                "class": 'search',
            }).appendTo(dropdown);

            $('<input />', {
                "class": 'search-input',
                "name": 'search',
                "placeholder": settings.searchPlaceholder,
                keyup: function(event){
                    var searchText = $(this).val();
                    var newDropdownData =[];
                    settings.data.forEach(function(item){
                        if(item.name.toLowerCase().indexOf(searchText.toLowerCase()) >= 0){
                            newDropdownData.push(item);
                        }
                    })
                    reRenderdropdown(newDropdownData,event);
                }
            }).appendTo(search); 
        }

        if(MultiSelectData.length > 0){
            var itemsContainer = $('<div />', {
                "class": 'items-container'
            }).appendTo(dropdown);

            $.each(MultiSelectData, function(index,obj){
                var list = $('<div />', {
                    "class": 'item',
                    "data-id": index,
                    text: obj.name,
                    click: function(e){
                        closeDropdown(e);
                        if(settings.onOptionSelect && typeof settings.onOptionSelect === "function"){
                            settings.onOptionSelect(obj.name);
                        }
                    }
                }).appendTo(itemsContainer);    
            });
        }


        // Close Dropdown when click outside
        $(document).on("click",function(event) {
            var ele = $('.multiselect-container');
            if(!ele.find(event.target).length > 0 && $('.multiselect-container').hasClass("show")){
                closeDropdown();
            }
        });
        





        return this.append(wrapper);
        this.append('<div class="multiselect-wrapper"></div>')
 
    };
 
}( jQuery ));