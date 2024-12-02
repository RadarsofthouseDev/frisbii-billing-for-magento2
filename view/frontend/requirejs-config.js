var config = {
    paths: {
        'Reepay': 'https://checkout.reepay.com/checkout'
    },
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'Radarsofthouse_BillwerkPlusSubscription/js/swatch-renderer-override': true
            }
        }
    }
};