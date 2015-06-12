# fuck-csujw

怒艹中南教务网验证码

## 说明

教务网站验证码识别，2000个数据集测试大概有70%的正确率吧。

获取验证码去除背景、噪点、分割，取得四个字符保存为25*25=625阶的向量。这部分是PHP的。

逻辑回归(LogisticRegression)训练36个分类器，用没有优化的梯度下降，这部分是Python的。

## License

[MIT license](http://opensource.org/licenses/MIT)