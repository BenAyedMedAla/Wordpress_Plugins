
jQuery(document).ready(function($) {
    $('#articles-search').on('input', function() {
        filterArticles(false);
    });

    $('#articles-category-filter, #articles-brand-filter').change(function() {
        filterArticles(false);
    });

    function filterArticles(exactMatch) {
        var category = $('#articles-category-filter').val();
        var brand = $('#articles-brand-filter').val();
        var search = $('#articles-search').val().toLowerCase();

        console.log("Filtering articles with search term: ", search);

        // Conditions based on the search input
        if (search === '') {
            console.log("Search is empty");
            // Action if search input is empty
        } else if (search.length < 3) {
            console.log("Search is too short");
            // Action if search input is too short
        } else if (search.includes('special')) {
            console.log("Special condition met");
            // Action if search input contains 'special'
        }

        $('.article-item').each(function() {
            var itemCategory = $(this).find('p:contains("Category:")').text().replace('Category: ', '').toLowerCase();
            var itemBrand = $(this).find('p:contains("Brand:")').text().replace('Brand: ', '').toLowerCase();
            var itemName = $(this).find('h2').text().toLowerCase();

            var match = (!category || category === itemCategory) &&
                        (!brand || brand === itemBrand) &&
                        (!search || (exactMatch ? itemName === search : itemName.startsWith(search)));

            console.log("Article: ", itemName, " Match: ", match);

            $(this).toggle(match);
        });
    }
});
